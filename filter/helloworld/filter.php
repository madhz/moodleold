<?php
class filter_helloworld extends moodle_text_filter {
   // public function filter($text, array $options = array()) {
   //      return str_replace('world', 'hello world!', $text);
   //  }
    // public function filter($text, array $options = array()) {
    //     global $CFG;
    //     return str_replace($CFG->filter_helloworld/word,
    //             "hello $CFG->filter_helloworld/word!", $text);
    // }
    public function filter($text, array $options = array()) {
        global $CFG;
        if (isset($this->localconfig['word'])) {
            $word = $this->localconfig['word'];
        } else {
            $word = $CFG->filter_helloworld/word;
        }
        return str_replace($word, "hello $word!", $text);
    }
}
?>