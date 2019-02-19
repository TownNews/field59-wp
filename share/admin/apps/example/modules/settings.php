<?php

require_once 'tncms.php';

class TNCMS_Admin_AppModule_Example_settings extends TNCMS_Admin_Module {
        
    /* {{{ save( \TNCMS\Admin\Request $oReq ) */
    
    protected function action_save( $oReq ) {
        // Collect form data and save it 
        echo $this->jsonEncode(['success' => true]);
    }
    
    /* }}} */
    /* {{{ load( \TNCMS\Admin\Request $oReq ) */
    
    protected function action_load( $oReq ) {
        // Load configuration
        echo $this->jsonEncode([
            'success' => true,
            'data' => [
                'example_setting' => 'setting value',
                'feature_enabled' => 1,
                'other_setting' => 'other setting value',
                'other_thing_enabled' => 1
            ]
        ]);
    }
    
    /* }}} */
}