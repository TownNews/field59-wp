<?php

require_once 'tncms.php';

class TNCMS_Admin_AppModule_Example_main extends TNCMS_Admin_Module {
    private $m_nPageSize = 25;
    
    /* {{{ initialize */
    
    protected function initialize() {
        // module initialization       
    }
    
    /* }}} */
    /* {{{ array action_main */
    
    protected function action_main () {
        $this->echoStandardUI([
            // At minimum the JS file defining the UI object
            // @see main.js
            'js' => [
                'example/ui/settings.js',
                'example/ui/main.js'
            ],
            // data your app may rely on at startup
            'appConfig' => $this->_appConfig(),
            // this does not change
            'extVersion' => '3.*'
        ]);
    }
    
    /* }}} */
    /* {{{ search */
    
    protected function action_search( $oReq ) {
        // get any query params from $oReq
        $aResults = [];
        
        for ( $n = 1; $n <= $this->m_nPageSize; $n++ ) {
            $aResults[] = [
                'id' => 'item-' . $n,
                'title' => 'Item ' . $n,
                'summary' => 'Summary of item ' . $n
            ];
        }
        
        echo $this->jsonEncode([
            'success' => true,
            'data' => $aResults,
            'total' => count($aResults)
        ]);
    }
    
    /* }}} */
    /* {{{ _appConfig() */
    
    private function _appConfig() {
        return [
            'pageSize' => $this->m_nPageSize        
        ];
    }
    
    /* }}} */
}