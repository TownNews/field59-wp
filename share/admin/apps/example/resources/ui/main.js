/* {{{ UI */

// @see http://docs.sencha.com/extjs/3.4.0/

var UI = {

    _oVP: null,

    initialize: function ( oConfig ) {        
        var oResults = TNCMS.API.getNew('example/search/resultsPanel', {
        	region: 'center'
        })        

        var oSearch = TNCMS.API.getNew('example/search/searchPanel', {
            region: 'west',
            width: 230,
			cmargins: '0 5 0 0',
            split: true,
            collapsible: true
        });
        
        var oSettings = new TNCMS.Example.Settings({
        	region: 'east'
        });
        
        this._oVP = new Ext.Viewport({
            layout: 'border',
            items: [ oSearch, oResults, oSettings ]
        });

        // Execute an initial search
        oSearch.search();
    }

};

/* }}} */
