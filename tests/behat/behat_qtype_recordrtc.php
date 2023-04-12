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
 * Behat steps definitions for record media question type.
 *
 * @package   qtype_recordrtc
 * @category  test
 * @copyright 2020 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../../lib/behat/behat_base.php');

/**
 * Steps definitions related with the record media question type.
 */
class behat_qtype_recordrtc extends behat_base {

    /**
     * Make it as if the specified user has recorded the given fixture file.
     *
     * At the moment, this only works if there is only one recording question on the screen.
     *
     * It should not be necessary to pass the username, but we could not find a good way to work it out.
     *
     * @param string $username the username to be used for getting the conterxt.
     * @param string $fixturefile the full name (filename.extension) of the fixturefile.
     *
     * @When :username has recorded :fixturefile into the record RTC question
     */
    public function i_have_recorded_fixture($username, $fixturefile) {
        $this->i_have_recorded_fixture_into_inputname($username, $fixturefile, 'audio', 'recording');
    }

    /**
     * Make it as if the specified user has recorded the given fixture file.
     *
     * At the moment, this only works if there is only one recording question on the screen.
     *
     * It should not be necesssary to pass the username, but we could not find a good way to work it out.
     *
     * @param string $username the username to be used for getting the conterxt.
     * @param string $fixturefile the full name (filename.extension) of the fixturefile/
     * @param string $mediatype the media type (audio/video)
     * @param string $inputname the filename
     * @throws \Behat\Mink\Exception\ElementNotFoundException
     * @throws dml_exception
     * @throws file_exception
     * @throws stored_file_creation_exception
     * @When :username has recorded :fixturefile as :type into input :inputname of the record RTC question
     */
    public function i_have_recorded_fixture_into_inputname($username, $fixturefile, $mediatype, $inputname) {
        global $DB;

        $draftitemidnode = $this->get_selected_node('xpath_element', "//input[@type='hidden' and contains(@name, '_recording')]");
        $draftitemid = $draftitemidnode->getValue();

        $filename = (new qtype_recordrtc())->get_media_filename($inputname, $mediatype);
        // To test the backwards compatibility, allow mp3 files to be uploaded.
        if (substr($fixturefile, -4) === '.mp3') {
            $filename = substr($filename, 0, -4) . '.mp3';
        }

        $user = $DB->get_record('user', ['username' => $username]);
        // Create the file in the provided draft area.
        $fileinfo = [
            'contextid' => context_user::instance($user->id)->id,
            'component' => 'user',
            'filearea'  => 'draft',
            'itemid'    => $draftitemid,
            'filepath'  => '/',
            'filename'  => $filename,
        ];
        $fs = get_file_storage();
        $fs->create_file_from_pathname($fileinfo, __DIR__ . '/../fixtures/' . $fixturefile);

        $this->execute_script("
                (function() {
                    var button = document.querySelector('.que.recordrtc input.submit, .que.recordrtc button.submit');
                    if (button) {
                        button.disabled = false;
                    }
                }())"
        );
    }
}
