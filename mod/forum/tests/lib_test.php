<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The module forums tests
 *
 * @package    mod_forum
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/forum/lib.php');
require_once($CFG->dirroot . '/rating/lib.php');

class mod_forum_lib_testcase extends advanced_testcase {

    public function test_forum_trigger_content_uploaded_event() {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $forum = $this->getDataGenerator()->create_module('forum', array('course' => $course->id));
        $context = context_module::instance($forum->cmid);

        $this->setUser($user->id);
        $fakepost = (object) array('id' => 123, 'message' => 'Yay!', 'discussion' => 100);
        $cm = get_coursemodule_from_instance('forum', $forum->id);

        $fs = get_file_storage();
        $dummy = (object) array(
            'contextid' => $context->id,
            'component' => 'mod_forum',
            'filearea' => 'attachment',
            'itemid' => $fakepost->id,
            'filepath' => '/',
            'filename' => 'myassignmnent.pdf'
        );
        $fi = $fs->create_file_from_string($dummy, 'Content of ' . $dummy->filename);

        $data = new stdClass();
        $sink = $this->redirectEvents();
        forum_trigger_content_uploaded_event($fakepost, $cm, 'some triggered from value');
        $events = $sink->get_events();

        $this->assertCount(1, $events);
        $event = reset($events);
        $this->assertInstanceOf('\mod_forum\event\assessable_uploaded', $event);
        $this->assertEquals($context->id, $event->contextid);
        $this->assertEquals($fakepost->id, $event->objectid);
        $this->assertEquals($fakepost->message, $event->other['content']);
        $this->assertEquals($fakepost->discussion, $event->other['discussionid']);
        $this->assertCount(1, $event->other['pathnamehashes']);
        $this->assertEquals($fi->get_pathnamehash(), $event->other['pathnamehashes'][0]);
        $expected = new stdClass();
        $expected->modulename = 'forum';
        $expected->name = 'some triggered from value';
        $expected->cmid = $forum->cmid;
        $expected->itemid = $fakepost->id;
        $expected->courseid = $course->id;
        $expected->userid = $user->id;
        $expected->content = $fakepost->message;
        $expected->pathnamehashes = array($fi->get_pathnamehash());
        $this->assertEventLegacyData($expected, $event);
        $this->assertEventContextNotUsed($event);
    }

    public function test_forum_get_courses_user_posted_in() {
        $this->resetAfterTest();

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        $course3 = $this->getDataGenerator()->create_course();

        // Create 3 forums, one in each course.
        $record = new stdClass();
        $record->course = $course1->id;
        $forum1 = $this->getDataGenerator()->create_module('forum', $record);

        $record = new stdClass();
        $record->course = $course2->id;
        $forum2 = $this->getDataGenerator()->create_module('forum', $record);

        $record = new stdClass();
        $record->course = $course3->id;
        $forum3 = $this->getDataGenerator()->create_module('forum', $record);

        // Add a second forum in course 1.
        $record = new stdClass();
        $record->course = $course1->id;
        $forum4 = $this->getDataGenerator()->create_module('forum', $record);

        // Add discussions to course 1 started by user1.
        $record = new stdClass();
        $record->course = $course1->id;
        $record->userid = $user1->id;
        $record->forum = $forum1->id;
        $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        $record = new stdClass();
        $record->course = $course1->id;
        $record->userid = $user1->id;
        $record->forum = $forum4->id;
        $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        // Add discussions to course2 started by user1.
        $record = new stdClass();
        $record->course = $course2->id;
        $record->userid = $user1->id;
        $record->forum = $forum2->id;
        $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        // Add discussions to course 3 started by user2.
        $record = new stdClass();
        $record->course = $course3->id;
        $record->userid = $user2->id;
        $record->forum = $forum3->id;
        $discussion3 = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        // Add post to course 3 by user1.
        $record = new stdClass();
        $record->course = $course3->id;
        $record->userid = $user1->id;
        $record->forum = $forum3->id;
        $record->discussion = $discussion3->id;
        $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_post($record);

        // User 3 hasn't posted anything, so shouldn't get any results.
        $user3courses = forum_get_courses_user_posted_in($user3);
        $this->assertEmpty($user3courses);

        // User 2 has only posted in course3.
        $user2courses = forum_get_courses_user_posted_in($user2);
        $this->assertCount(1, $user2courses);
        $user2course = array_shift($user2courses);
        $this->assertEquals($course3->id, $user2course->id);
        $this->assertEquals($course3->shortname, $user2course->shortname);

        // User 1 has posted in all 3 courses.
        $user1courses = forum_get_courses_user_posted_in($user1);
        $this->assertCount(3, $user1courses);
        foreach ($user1courses as $course) {
            $this->assertContains($course->id, array($course1->id, $course2->id, $course3->id));
            $this->assertContains($course->shortname, array($course1->shortname, $course2->shortname,
                $course3->shortname));

        }

        // User 1 has only started a discussion in course 1 and 2 though.
        $user1courses = forum_get_courses_user_posted_in($user1, true);
        $this->assertCount(2, $user1courses);
        foreach ($user1courses as $course) {
            $this->assertContains($course->id, array($course1->id, $course2->id));
            $this->assertContains($course->shortname, array($course1->shortname, $course2->shortname));
        }
    }

    /**
     * Test the logic in the forum_tp_can_track_forums() function.
     */
    public function test_forum_tp_can_track_forums() {
        global $CFG;

        $this->resetAfterTest();

        $useron = $this->getDataGenerator()->create_user(array('trackforums' => 1));
        $useroff = $this->getDataGenerator()->create_user(array('trackforums' => 0));
        $course = $this->getDataGenerator()->create_course();
        $options = array('course' => $course->id, 'trackingtype' => FORUM_TRACKING_OFF); // Off.
        $forumoff = $this->getDataGenerator()->create_module('forum', $options);

        $options = array('course' => $course->id, 'trackingtype' => FORUM_TRACKING_FORCED); // On.
        $forumforce = $this->getDataGenerator()->create_module('forum', $options);

        $options = array('course' => $course->id, 'trackingtype' => FORUM_TRACKING_OPTIONAL); // Optional.
        $forumoptional = $this->getDataGenerator()->create_module('forum', $options);

        // Allow force.
        $CFG->forum_allowforcedreadtracking = 1;

        // User on, forum off, should be off.
        $result = forum_tp_can_track_forums($forumoff, $useron);
        $this->assertEquals(false, $result);

        // User on, forum on, should be on.
        $result = forum_tp_can_track_forums($forumforce, $useron);
        $this->assertEquals(true, $result);

        // User on, forum optional, should be on.
        $result = forum_tp_can_track_forums($forumoptional, $useron);
        $this->assertEquals(true, $result);

        // User off, forum off, should be off.
        $result = forum_tp_can_track_forums($forumoff, $useroff);
        $this->assertEquals(false, $result);

        // User off, forum force, should be on.
        $result = forum_tp_can_track_forums($forumforce, $useroff);
        $this->assertEquals(true, $result);

        // User off, forum optional, should be off.
        $result = forum_tp_can_track_forums($forumoptional, $useroff);
        $this->assertEquals(false, $result);

        // Don't allow force.
        $CFG->forum_allowforcedreadtracking = 0;

        // User on, forum off, should be off.
        $result = forum_tp_can_track_forums($forumoff, $useron);
        $this->assertEquals(false, $result);

        // User on, forum on, should be on.
        $result = forum_tp_can_track_forums($forumforce, $useron);
        $this->assertEquals(true, $result);

        // User on, forum optional, should be on.
        $result = forum_tp_can_track_forums($forumoptional, $useron);
        $this->assertEquals(true, $result);

        // User off, forum off, should be off.
        $result = forum_tp_can_track_forums($forumoff, $useroff);
        $this->assertEquals(false, $result);

        // User off, forum force, should be off.
        $result = forum_tp_can_track_forums($forumforce, $useroff);
        $this->assertEquals(false, $result);

        // User off, forum optional, should be off.
        $result = forum_tp_can_track_forums($forumoptional, $useroff);
        $this->assertEquals(false, $result);

    }

    /**
     * Test the logic in the test_forum_tp_is_tracked() function.
     */
    public function test_forum_tp_is_tracked() {
        global $CFG;

        $this->resetAfterTest();

        $useron = $this->getDataGenerator()->create_user(array('trackforums' => 1));
        $useroff = $this->getDataGenerator()->create_user(array('trackforums' => 0));
        $course = $this->getDataGenerator()->create_course();
        $options = array('course' => $course->id, 'trackingtype' => FORUM_TRACKING_OFF); // Off.
        $forumoff = $this->getDataGenerator()->create_module('forum', $options);

        $options = array('course' => $course->id, 'trackingtype' => FORUM_TRACKING_FORCED); // On.
        $forumforce = $this->getDataGenerator()->create_module('forum', $options);

        $options = array('course' => $course->id, 'trackingtype' => FORUM_TRACKING_OPTIONAL); // Optional.
        $forumoptional = $this->getDataGenerator()->create_module('forum', $options);

        // Allow force.
        $CFG->forum_allowforcedreadtracking = 1;

        // User on, forum off, should be off.
        $result = forum_tp_is_tracked($forumoff, $useron);
        $this->assertEquals(false, $result);

        // User on, forum force, should be on.
        $result = forum_tp_is_tracked($forumforce, $useron);
        $this->assertEquals(true, $result);

        // User on, forum optional, should be on.
        $result = forum_tp_is_tracked($forumoptional, $useron);
        $this->assertEquals(true, $result);

        // User off, forum off, should be off.
        $result = forum_tp_is_tracked($forumoff, $useroff);
        $this->assertEquals(false, $result);

        // User off, forum force, should be on.
        $result = forum_tp_is_tracked($forumforce, $useroff);
        $this->assertEquals(true, $result);

        // User off, forum optional, should be off.
        $result = forum_tp_is_tracked($forumoptional, $useroff);
        $this->assertEquals(false, $result);

        // Don't allow force.
        $CFG->forum_allowforcedreadtracking = 0;

        // User on, forum off, should be off.
        $result = forum_tp_is_tracked($forumoff, $useron);
        $this->assertEquals(false, $result);

        // User on, forum force, should be on.
        $result = forum_tp_is_tracked($forumforce, $useron);
        $this->assertEquals(true, $result);

        // User on, forum optional, should be on.
        $result = forum_tp_is_tracked($forumoptional, $useron);
        $this->assertEquals(true, $result);

        // User off, forum off, should be off.
        $result = forum_tp_is_tracked($forumoff, $useroff);
        $this->assertEquals(false, $result);

        // User off, forum force, should be off.
        $result = forum_tp_is_tracked($forumforce, $useroff);
        $this->assertEquals(false, $result);

        // User off, forum optional, should be off.
        $result = forum_tp_is_tracked($forumoptional, $useroff);
        $this->assertEquals(false, $result);

        // Stop tracking so we can test again.
        forum_tp_stop_tracking($forumforce->id, $useron->id);
        forum_tp_stop_tracking($forumoptional->id, $useron->id);
        forum_tp_stop_tracking($forumforce->id, $useroff->id);
        forum_tp_stop_tracking($forumoptional->id, $useroff->id);

        // Allow force.
        $CFG->forum_allowforcedreadtracking = 1;

        // User on, preference off, forum force, should be on.
        $result = forum_tp_is_tracked($forumforce, $useron);
        $this->assertEquals(true, $result);

        // User on, preference off, forum optional, should be on.
        $result = forum_tp_is_tracked($forumoptional, $useron);
        $this->assertEquals(false, $result);

        // User off, preference off, forum force, should be on.
        $result = forum_tp_is_tracked($forumforce, $useroff);
        $this->assertEquals(true, $result);

        // User off, preference off, forum optional, should be off.
        $result = forum_tp_is_tracked($forumoptional, $useroff);
        $this->assertEquals(false, $result);

        // Don't allow force.
        $CFG->forum_allowforcedreadtracking = 0;

        // User on, preference off, forum force, should be on.
        $result = forum_tp_is_tracked($forumforce, $useron);
        $this->assertEquals(false, $result);

        // User on, preference off, forum optional, should be on.
        $result = forum_tp_is_tracked($forumoptional, $useron);
        $this->assertEquals(false, $result);

        // User off, preference off, forum force, should be off.
        $result = forum_tp_is_tracked($forumforce, $useroff);
        $this->assertEquals(false, $result);

        // User off, preference off, forum optional, should be off.
        $result = forum_tp_is_tracked($forumoptional, $useroff);
        $this->assertEquals(false, $result);
    }

    /**
     * Test the logic in the forum_tp_get_course_unread_posts() function.
     */
    public function test_forum_tp_get_course_unread_posts() {
        global $CFG;

        $this->resetAfterTest();

        $useron = $this->getDataGenerator()->create_user(array('trackforums' => 1));
        $useroff = $this->getDataGenerator()->create_user(array('trackforums' => 0));
        $course = $this->getDataGenerator()->create_course();
        $options = array('course' => $course->id, 'trackingtype' => FORUM_TRACKING_OFF); // Off.
        $forumoff = $this->getDataGenerator()->create_module('forum', $options);

        $options = array('course' => $course->id, 'trackingtype' => FORUM_TRACKING_FORCED); // On.
        $forumforce = $this->getDataGenerator()->create_module('forum', $options);

        $options = array('course' => $course->id, 'trackingtype' => FORUM_TRACKING_OPTIONAL); // Optional.
        $forumoptional = $this->getDataGenerator()->create_module('forum', $options);

        // Add discussions to the tracking off forum.
        $record = new stdClass();
        $record->course = $course->id;
        $record->userid = $useron->id;
        $record->forum = $forumoff->id;
        $discussionoff = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        // Add discussions to the tracking forced forum.
        $record = new stdClass();
        $record->course = $course->id;
        $record->userid = $useron->id;
        $record->forum = $forumforce->id;
        $discussionforce = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        // Add post to the tracking forced discussion.
        $record = new stdClass();
        $record->course = $course->id;
        $record->userid = $useroff->id;
        $record->forum = $forumforce->id;
        $record->discussion = $discussionforce->id;
        $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_post($record);

        // Add discussions to the tracking optional forum.
        $record = new stdClass();
        $record->course = $course->id;
        $record->userid = $useron->id;
        $record->forum = $forumoptional->id;
        $discussionoptional = $this->getDataGenerator()->get_plugin_generator('mod_forum')->create_discussion($record);

        // Allow force.
        $CFG->forum_allowforcedreadtracking = 1;

        $result = forum_tp_get_course_unread_posts($useron->id, $course->id);
        $this->assertEquals(2, count($result));
        $this->assertEquals(false, isset($result[$forumoff->id]));
        $this->assertEquals(true, isset($result[$forumforce->id]));
        $this->assertEquals(2, $result[$forumforce->id]->unread);
        $this->assertEquals(true, isset($result[$forumoptional->id]));
        $this->assertEquals(1, $result[$forumoptional->id]->unread);

        $result = forum_tp_get_course_unread_posts($useroff->id, $course->id);
        $this->assertEquals(1, count($result));
        $this->assertEquals(false, isset($result[$forumoff->id]));
        $this->assertEquals(true, isset($result[$forumforce->id]));
        $this->assertEquals(2, $result[$forumforce->id]->unread);
        $this->assertEquals(false, isset($result[$forumoptional->id]));

        // Don't allow force.
        $CFG->forum_allowforcedreadtracking = 0;

        $result = forum_tp_get_course_unread_posts($useron->id, $course->id);
        $this->assertEquals(2, count($result));
        $this->assertEquals(false, isset($result[$forumoff->id]));
        $this->assertEquals(true, isset($result[$forumforce->id]));
        $this->assertEquals(2, $result[$forumforce->id]->unread);
        $this->assertEquals(true, isset($result[$forumoptional->id]));
        $this->assertEquals(1, $result[$forumoptional->id]->unread);

        $result = forum_tp_get_course_unread_posts($useroff->id, $course->id);
        $this->assertEquals(0, count($result));
        $this->assertEquals(false, isset($result[$forumoff->id]));
        $this->assertEquals(false, isset($result[$forumforce->id]));
        $this->assertEquals(false, isset($result[$forumoptional->id]));

        // Stop tracking so we can test again.
        forum_tp_stop_tracking($forumforce->id, $useron->id);
        forum_tp_stop_tracking($forumoptional->id, $useron->id);
        forum_tp_stop_tracking($forumforce->id, $useroff->id);
        forum_tp_stop_tracking($forumoptional->id, $useroff->id);

        // Allow force.
        $CFG->forum_allowforcedreadtracking = 1;

        $result = forum_tp_get_course_unread_posts($useron->id, $course->id);
        $this->assertEquals(1, count($result));
        $this->assertEquals(false, isset($result[$forumoff->id]));
        $this->assertEquals(true, isset($result[$forumforce->id]));
        $this->assertEquals(2, $result[$forumforce->id]->unread);
        $this->assertEquals(false, isset($result[$forumoptional->id]));

        $result = forum_tp_get_course_unread_posts($useroff->id, $course->id);
        $this->assertEquals(1, count($result));
        $this->assertEquals(false, isset($result[$forumoff->id]));
        $this->assertEquals(true, isset($result[$forumforce->id]));
        $this->assertEquals(2, $result[$forumforce->id]->unread);
        $this->assertEquals(false, isset($result[$forumoptional->id]));

        // Don't allow force.
        $CFG->forum_allowforcedreadtracking = 0;

        $result = forum_tp_get_course_unread_posts($useron->id, $course->id);
        $this->assertEquals(0, count($result));
        $this->assertEquals(false, isset($result[$forumoff->id]));
        $this->assertEquals(false, isset($result[$forumforce->id]));
        $this->assertEquals(false, isset($result[$forumoptional->id]));

        $result = forum_tp_get_course_unread_posts($useroff->id, $course->id);
        $this->assertEquals(0, count($result));
        $this->assertEquals(false, isset($result[$forumoff->id]));
        $this->assertEquals(false, isset($result[$forumforce->id]));
        $this->assertEquals(false, isset($result[$forumoptional->id]));
    }

    /**
     * Test the logic in the test_forum_tp_get_untracked_forums() function.
     */
    public function test_forum_tp_get_untracked_forums() {
        global $CFG;

        $this->resetAfterTest();

        $useron = $this->getDataGenerator()->create_user(array('trackforums' => 1));
        $useroff = $this->getDataGenerator()->create_user(array('trackforums' => 0));
        $course = $this->getDataGenerator()->create_course();
        $options = array('course' => $course->id, 'trackingtype' => FORUM_TRACKING_OFF); // Off.
        $forumoff = $this->getDataGenerator()->create_module('forum', $options);

        $options = array('course' => $course->id, 'trackingtype' => FORUM_TRACKING_FORCED); // On.
        $forumforce = $this->getDataGenerator()->create_module('forum', $options);

        $options = array('course' => $course->id, 'trackingtype' => FORUM_TRACKING_OPTIONAL); // Optional.
        $forumoptional = $this->getDataGenerator()->create_module('forum', $options);

        // Allow force.
        $CFG->forum_allowforcedreadtracking = 1;

        // On user with force on.
        $result = forum_tp_get_untracked_forums($useron->id, $course->id);
        $this->assertEquals(1, count($result));
        $this->assertEquals(true, isset($result[$forumoff->id]));

        // Off user with force on.
        $result = forum_tp_get_untracked_forums($useroff->id, $course->id);
        $this->assertEquals(2, count($result));
        $this->assertEquals(true, isset($result[$forumoff->id]));
        $this->assertEquals(true, isset($result[$forumoptional->id]));

        // Don't allow force.
        $CFG->forum_allowforcedreadtracking = 0;

        // On user with force off.
        $result = forum_tp_get_untracked_forums($useron->id, $course->id);
        $this->assertEquals(1, count($result));
        $this->assertEquals(true, isset($result[$forumoff->id]));

        // Off user with force off.
        $result = forum_tp_get_untracked_forums($useroff->id, $course->id);
        $this->assertEquals(3, count($result));
        $this->assertEquals(true, isset($result[$forumoff->id]));
        $this->assertEquals(true, isset($result[$forumoptional->id]));
        $this->assertEquals(true, isset($result[$forumforce->id]));

        // Stop tracking so we can test again.
        forum_tp_stop_tracking($forumforce->id, $useron->id);
        forum_tp_stop_tracking($forumoptional->id, $useron->id);
        forum_tp_stop_tracking($forumforce->id, $useroff->id);
        forum_tp_stop_tracking($forumoptional->id, $useroff->id);

        // Allow force.
        $CFG->forum_allowforcedreadtracking = 1;

        // On user with force on.
        $result = forum_tp_get_untracked_forums($useron->id, $course->id);
        $this->assertEquals(2, count($result));
        $this->assertEquals(true, isset($result[$forumoff->id]));
        $this->assertEquals(true, isset($result[$forumoptional->id]));

        // Off user with force on.
        $result = forum_tp_get_untracked_forums($useroff->id, $course->id);
        $this->assertEquals(2, count($result));
        $this->assertEquals(true, isset($result[$forumoff->id]));
        $this->assertEquals(true, isset($result[$forumoptional->id]));

        // Don't allow force.
        $CFG->forum_allowforcedreadtracking = 0;

        // On user with force off.
        $result = forum_tp_get_untracked_forums($useron->id, $course->id);
        $this->assertEquals(3, count($result));
        $this->assertEquals(true, isset($result[$forumoff->id]));
        $this->assertEquals(true, isset($result[$forumoptional->id]));
        $this->assertEquals(true, isset($result[$forumforce->id]));

        // Off user with force off.
        $result = forum_tp_get_untracked_forums($useroff->id, $course->id);
        $this->assertEquals(3, count($result));
        $this->assertEquals(true, isset($result[$forumoff->id]));
        $this->assertEquals(true, isset($result[$forumoptional->id]));
        $this->assertEquals(true, isset($result[$forumforce->id]));
    }

    /**
     * Test subscription using automatic subscription on create.
     */
    public function test_forum_auto_subscribe_on_create() {
        global $CFG;

        $this->resetAfterTest();

        $usercount = 5;
        $course = $this->getDataGenerator()->create_course();
        $users = array();

        for ($i = 0; $i < $usercount; $i++) {
            $user = $this->getDataGenerator()->create_user();
            $users[] = $user;
            $this->getDataGenerator()->enrol_user($user->id, $course->id);
        }

        $options = array('course' => $course->id, 'forcesubscribe' => FORUM_INITIALSUBSCRIBE); // Automatic Subscription.
        $forum = $this->getDataGenerator()->create_module('forum', $options);

        $result = forum_subscribed_users($course, $forum);
        $this->assertEquals($usercount, count($result));
        foreach ($users as $user) {
            $this->assertTrue(forum_is_subscribed($user->id, $forum));
        }
    }

    /**
     * Test subscription using forced subscription on create.
     */
    public function test_forum_forced_subscribe_on_create() {
        global $CFG;

        $this->resetAfterTest();

        $usercount = 5;
        $course = $this->getDataGenerator()->create_course();
        $users = array();

        for ($i = 0; $i < $usercount; $i++) {
            $user = $this->getDataGenerator()->create_user();
            $users[] = $user;
            $this->getDataGenerator()->enrol_user($user->id, $course->id);
        }

        $options = array('course' => $course->id, 'forcesubscribe' => FORUM_FORCESUBSCRIBE); // Forced subscription.
        $forum = $this->getDataGenerator()->create_module('forum', $options);

        $result = forum_subscribed_users($course, $forum);
        $this->assertEquals($usercount, count($result));
        foreach ($users as $user) {
            $this->assertTrue(forum_is_subscribed($user->id, $forum));
        }
    }

    /**
     * Test subscription using optional subscription on create.
     */
    public function test_forum_optional_subscribe_on_create() {
        global $CFG;

        $this->resetAfterTest();

        $usercount = 5;
        $course = $this->getDataGenerator()->create_course();
        $users = array();

        for ($i = 0; $i < $usercount; $i++) {
            $user = $this->getDataGenerator()->create_user();
            $users[] = $user;
            $this->getDataGenerator()->enrol_user($user->id, $course->id);
        }

        $options = array('course' => $course->id, 'forcesubscribe' => FORUM_CHOOSESUBSCRIBE); // Subscription optional.
        $forum = $this->getDataGenerator()->create_module('forum', $options);

        $result = forum_subscribed_users($course, $forum);
        // No subscriptions by default.
        $this->assertEquals(0, count($result));
        foreach ($users as $user) {
            $this->assertFalse(forum_is_subscribed($user->id, $forum));
        }
    }

    /**
     * Test subscription using disallow subscription on create.
     */
    public function test_forum_disallow_subscribe_on_create() {
        global $CFG;

        $this->resetAfterTest();

        $usercount = 5;
        $course = $this->getDataGenerator()->create_course();
        $users = array();

        for ($i = 0; $i < $usercount; $i++) {
            $user = $this->getDataGenerator()->create_user();
            $users[] = $user;
            $this->getDataGenerator()->enrol_user($user->id, $course->id);
        }

        $options = array('course' => $course->id, 'forcesubscribe' => FORUM_DISALLOWSUBSCRIBE); // Subscription prevented.
        $forum = $this->getDataGenerator()->create_module('forum', $options);

        $result = forum_subscribed_users($course, $forum);
        // No subscriptions by default.
        $this->assertEquals(0, count($result));
        foreach ($users as $user) {
            $this->assertFalse(forum_is_subscribed($user->id, $forum));
        }
    }

    public function test_count_discussion_replies_basic() {
        list($forum, $discussionids) = $this->create_multiple_discussions_with_replies(10, 5);

        // Count the discussion replies in the forum.
        $result = forum_count_discussion_replies($forum->id);
        $this->assertCount(10, $result);
    }

    public function test_count_discussion_replies_limited() {
        list($forum, $discussionids) = $this->create_multiple_discussions_with_replies(10, 5);
        // Adding limits shouldn't make a difference.
        $result = forum_count_discussion_replies($forum->id, "", 20);
        $this->assertCount(10, $result);
    }

    public function test_count_discussion_replies_paginated() {
        list($forum, $discussionids) = $this->create_multiple_discussions_with_replies(10, 5);
        // Adding paging shouldn't make any difference.
        $result = forum_count_discussion_replies($forum->id, "", -1, 0, 100);
        $this->assertCount(10, $result);
    }

    public function test_count_discussion_replies_paginated_sorted() {
        list($forum, $discussionids) = $this->create_multiple_discussions_with_replies(10, 5);
        // Specifying the forumsort should also give a good result. This follows a different path.
        $result = forum_count_discussion_replies($forum->id, "d.id asc", -1, 0, 100);
        $this->assertCount(10, $result);
        foreach ($result as $row) {
            // Grab the first discussionid.
            $discussionid = array_shift($discussionids);
            $this->assertEquals($discussionid, $row->discussion);
        }
    }

    public function test_count_discussion_replies_limited_sorted() {
        list($forum, $discussionids) = $this->create_multiple_discussions_with_replies(10, 5);
        // Adding limits, and a forumsort shouldn't make a difference.
        $result = forum_count_discussion_replies($forum->id, "d.id asc", 20);
        $this->assertCount(10, $result);
        foreach ($result as $row) {
            // Grab the first discussionid.
            $discussionid = array_shift($discussionids);
            $this->assertEquals($discussionid, $row->discussion);
        }
    }

    public function test_count_discussion_replies_paginated_sorted_small() {
        list($forum, $discussionids) = $this->create_multiple_discussions_with_replies(10, 5);
        // Grabbing a smaller subset and they should be ordered as expected.
        $result = forum_count_discussion_replies($forum->id, "d.id asc", -1, 0, 5);
        $this->assertCount(5, $result);
        foreach ($result as $row) {
            // Grab the first discussionid.
            $discussionid = array_shift($discussionids);
            $this->assertEquals($discussionid, $row->discussion);
        }
    }

    public function test_count_discussion_replies_paginated_sorted_small_reverse() {
        list($forum, $discussionids) = $this->create_multiple_discussions_with_replies(10, 5);
        // Grabbing a smaller subset and they should be ordered as expected.
        $result = forum_count_discussion_replies($forum->id, "d.id desc", -1, 0, 5);
        $this->assertCount(5, $result);
        foreach ($result as $row) {
            // Grab the last discussionid.
            $discussionid = array_pop($discussionids);
            $this->assertEquals($discussionid, $row->discussion);
        }
    }

    public function test_count_discussion_replies_limited_sorted_small_reverse() {
        list($forum, $discussionids) = $this->create_multiple_discussions_with_replies(10, 5);
        // Adding limits, and a forumsort shouldn't make a difference.
        $result = forum_count_discussion_replies($forum->id, "d.id desc", 5);
        $this->assertCount(5, $result);
        foreach ($result as $row) {
            // Grab the last discussionid.
            $discussionid = array_pop($discussionids);
            $this->assertEquals($discussionid, $row->discussion);
        }
    }

    /**
     * Create a new course, forum, and user with a number of discussions and replies.
     *
     * @param int $discussioncount The number of discussions to create
     * @param int $replycount The number of replies to create in each discussion
     * @return array Containing the created forum object, and the ids of the created discussions.
     */
    protected function create_multiple_discussions_with_replies($discussioncount, $replycount) {
        $this->resetAfterTest();

        // Setup the content.
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $record = new stdClass();
        $record->course = $course->id;
        $forum = $this->getDataGenerator()->create_module('forum', $record);

        // Create 10 discussions with replies.
        $discussionids = array();
        for ($i = 0; $i < $discussioncount; $i++) {
            $discussion = $this->create_single_discussion_with_replies($forum, $user, $replycount);
            $discussionids[] = $discussion->id;
        }
        return array($forum, $discussionids);
    }

    /**
     * Create a discussion with a number of replies.
     *
     * @param object $forum The forum which has been created
     * @param object $user The user making the discussion and replies
     * @param int $replycount The number of replies
     * @return object $discussion
     */
    protected function create_single_discussion_with_replies($forum, $user, $replycount) {
        global $DB;

        $generator = self::getDataGenerator()->get_plugin_generator('mod_forum');

        $record = new stdClass();
        $record->course = $forum->course;
        $record->forum = $forum->id;
        $record->userid = $user->id;
        $discussion = $generator->create_discussion($record);

        // Retrieve the first post.
        $replyto = $DB->get_record('forum_posts', array('discussion' => $discussion->id));

        // Create the replies.
        $post = new stdClass();
        $post->userid = $user->id;
        $post->discussion = $discussion->id;
        $post->parent = $replyto->id;

        for ($i = 0; $i < $replycount; $i++) {
            $generator->create_post($post);
        }

        return $discussion;
    }

    /**
     * Tests for mod_forum_rating_can_see_item_ratings().
     *
     * @throws coding_exception
     * @throws rating_exception
     */
    public function test_mod_forum_rating_can_see_item_ratings() {
        global $DB;

        $this->resetAfterTest();

        // Setup test data.
        $course = new stdClass();
        $course->groupmode = SEPARATEGROUPS;
        $course->groupmodeforce = true;
        $course = $this->getDataGenerator()->create_course($course);
        $forum = $this->getDataGenerator()->create_module('forum', array('course' => $course->id));
        $generator = self::getDataGenerator()->get_plugin_generator('mod_forum');
        $cm = get_coursemodule_from_instance('forum', $forum->id);
        $context = context_module::instance($cm->id);

        // Create users.
        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        // Groups and stuff.
        $role = $DB->get_record('role', array('shortname' => 'teacher'), '*', MUST_EXIST);
        $this->getDataGenerator()->enrol_user($user1->id, $course->id, $role->id);
        $this->getDataGenerator()->enrol_user($user2->id, $course->id, $role->id);
        $this->getDataGenerator()->enrol_user($user3->id, $course->id, $role->id);
        $this->getDataGenerator()->enrol_user($user4->id, $course->id, $role->id);

        $group1 = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        $group2 = $this->getDataGenerator()->create_group(array('courseid' => $course->id));
        groups_add_member($group1, $user1);
        groups_add_member($group1, $user2);
        groups_add_member($group2, $user3);
        groups_add_member($group2, $user4);

        $record = new stdClass();
        $record->course = $forum->course;
        $record->forum = $forum->id;
        $record->userid = $user1->id;
        $record->groupid = $group1->id;
        $discussion = $generator->create_discussion($record);

        // Retrieve the first post.
        $post = $DB->get_record('forum_posts', array('discussion' => $discussion->id));

        $ratingoptions = new stdClass;
        $ratingoptions->context = $context;
        $ratingoptions->ratingarea = 'post';
        $ratingoptions->component = 'mod_forum';
        $ratingoptions->itemid  = $post->id;
        $ratingoptions->scaleid = 2;
        $ratingoptions->userid  = $user2->id;
        $rating = new rating($ratingoptions);
        $rating->update_rating(2);

        // Now try to access it as various users.
        unassign_capability('moodle/site:accessallgroups', $role->id);
        $params = array('contextid' => 2,
                        'component' => 'mod_forum',
                        'ratingarea' => 'post',
                        'itemid' => $post->id,
                        'scaleid' => 2);
        $this->setUser($user1);
        $this->assertTrue(mod_forum_rating_can_see_item_ratings($params));
        $this->setUser($user2);
        $this->assertTrue(mod_forum_rating_can_see_item_ratings($params));
        $this->setUser($user3);
        $this->assertFalse(mod_forum_rating_can_see_item_ratings($params));
        $this->setUser($user4);
        $this->assertFalse(mod_forum_rating_can_see_item_ratings($params));

        // Now try with accessallgroups cap and make sure everything is visible.
        assign_capability('moodle/site:accessallgroups', CAP_ALLOW, $role->id, $context->id);
        $this->setUser($user1);
        $this->assertTrue(mod_forum_rating_can_see_item_ratings($params));
        $this->setUser($user2);
        $this->assertTrue(mod_forum_rating_can_see_item_ratings($params));
        $this->setUser($user3);
        $this->assertTrue(mod_forum_rating_can_see_item_ratings($params));
        $this->setUser($user4);
        $this->assertTrue(mod_forum_rating_can_see_item_ratings($params));

        // Change group mode and verify visibility.
        $course->groupmode = VISIBLEGROUPS;
        $DB->update_record('course', $course);
        unassign_capability('moodle/site:accessallgroups', $role->id);
        $this->setUser($user1);
        $this->assertTrue(mod_forum_rating_can_see_item_ratings($params));
        $this->setUser($user2);
        $this->assertTrue(mod_forum_rating_can_see_item_ratings($params));
        $this->setUser($user3);
        $this->assertTrue(mod_forum_rating_can_see_item_ratings($params));
        $this->setUser($user4);
        $this->assertTrue(mod_forum_rating_can_see_item_ratings($params));

    }

}
