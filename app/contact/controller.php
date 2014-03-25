<?php
/**
 * mazurka FormFramework
 * Controller
 *
 * @version       1.0
 * @since         1.0
 * @copyright     Copyright (c) 2014 macchaka, Omura Printing Co.,ltd.
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

require_once (APP_DIR . '/mazurka.php');

class controller extends mazurka {
    public function first() {
        parent::first();
        $this->render();
    }

    public function back() {
        parent::back();
        $this->render();
    }

    public function proof() {
        parent::proof();
        $this->render();
    }

    public function send() {
        parent::send();
        $this->render();
    }
}
