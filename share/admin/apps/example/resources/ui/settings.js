(function () {

// @see http://docs.sencha.com/extjs/3.4.0/	
	
var saveSettings = function ( oData ) {
	console.log('Saving changes: ', oData);
	this.getEl().mask('Saving changes');
	
	Ext.Ajax.request({
		url: TNCMS.actionURL('example/settings/save'),
		params: oData,
		success: function ( oResp ) {
			this.getEl().unmask();
			oData = Ext.decode(oResp.responseText);
			// Do something with data if needed
		},
		failure: function ( oErr ) {
			this.getEl().unmask();
			// Deal with UI/state changes on failure
		},
		scope: this
	});
}	

/* {{{ GeneralPanel */

var GeneralPanel = Ext.extend(Ext.form.FormPanel, {
	
	/* {{{ setData */
	
	/**
	 * Set data loaded from server in to this form
	 * 
	 */
	setData: function ( oData ) {
		this.getForm().setValues(oData);
	},
	
	/* }}} */
	/* {{{ initComponent */
	
	/**
	 * Define this components configuration
	 * 
	 */ 
	initComponent: function () {
		
		this.title = 'General';
		this.layout = 'form';
		this.labelAlign = 'top';
		
		this.items = [{
    		xtype: 'fieldset',
    		title: 'Grouped settings',
    		layout: 'form',
    		defaults: { border: false, anchor: '100%' },        		
    		items: [{
        		xtype: 'textfield',
        		fieldLabel: 'Example config setting',
        		name: 'example_setting',
        		emptyText: 'Enter a value',
        		allowBlank: false
        	}, {
        		xtype: 'checkbox',
        		hideLabel: true,
        		name: 'feature_enabled',
        		boxLabel: 'Feature is enabled',
        		inputValue: 1
        	}]
		}];
		
        this.tbar = ['->', {
    		text: 'Save',
    		iconCls: 'tncms-icons-save',
    		scope: this,
    		handler: function () {
    			var oForm = this.getForm();    			
    			if ( ! oForm.isDirty() || ! oForm.isValid() ) return;
    			
    			// getFieldValues(true) returns only dirty fields
    			saveSettings.call(this, oForm.getFieldValues(true));
    		}
    	}];
		
		GeneralPanel.superclass.initComponent.call(this);
	}
	
	/* }}} */
	
});

/* {{{ OtherPanel */

var OtherPanel = Ext.extend(Ext.form.FormPanel, {
	
	/* {{{ setData */
	
	/**
	 * Set data loaded from server in to this form
	 * 
	 */
	setData: function ( oData ) {
		this.getForm().setValues(oData);
	},
	
	/* }}} */
	/* {{{ initComponent */
	
	/**
	 * Define this components configuration
	 * 
	 */ 
	initComponent: function () {
		
		this.title = 'Other';
		this.layout = 'form';
		this.labelAlign = 'top';
		
		this.items = [{
    		xtype: 'fieldset',
    		title: 'Grouped settings',
    		layout: 'form',
    		defaults: { border: false, anchor: '100%' },        		
    		items: [{
        		xtype: 'textfield',
        		name: 'other_setting',
        		fieldLabel: 'Example config setting',
        		emptyText: 'Enter a value',
        		allowBlank: false
        	}, {
        		xtype: 'checkbox',
        		hideLabel: true,
        		name: 'other_thing_enabled',
        		boxLabel: 'Other thing enabled',
        		inputValue: 1
        	}]
		}];
		
        this.tbar = ['->', {
    		text: 'Save',
    		iconCls: 'tncms-icons-save',
    		scope: this,
    		handler: function () {
    			var oForm = this.getForm();    			
    			if ( ! oForm.isDirty() || ! oForm.isValid() ) return;
    			
    			// getFieldValues(true) returns only dirty fields
    			saveSettings.call(this, oForm.getFieldValues(true));
    		}
    	}];
		
		GeneralPanel.superclass.initComponent.call(this);
	}
	
	/* }}} */
	
});

/* }}} */

Ext.namespace('TNCMS.Example');	
	
TNCMS.Example.Settings = Ext.extend(
TNCMS.App ? TNCMS.App.ConfigPanel : Ext.Panel, {
	
	/* {{{ loadData */
	
	/**
	 * Call out to the server for any existing 
	 * configuration and load form data upon success
	 * 
	 */
	loadData: function () {
		Ext.Ajax.request({
			scope: this,
			url: TNCMS.actionURL('example/settings/load'),
			success: function ( oResp ) {
				var oData = Ext.decode(oResp.responseText);
				if ( oData && oData.data ) {
					this.items.each(function(oItem) {
						oItem.setData(oData.data);
					});
				}
			},
			failure: function ( oErr ) {
				// Do something if needed
			}
		});
	},
	
	/* }}} */
	/* {{{ initComponent */
	
	/**
	 * Provide default layout and configuration
	 * for this component
	 * 
	 */
	initComponent: function () {
		
        this.layout = 'accordion';
        this.floatable = false;
        this.collapsible = true;
        this.collapsed = true;
        
        this.items = [
        	new GeneralPanel(),
        	new OtherPanel()
    	];
        
        this.listeners = {
    		scope: this,
    		beforeshow: this.loadData
        };
        
		TNCMS.Example.Settings.superclass.initComponent.call(this);
	}
	
	/* }}} */

});

})();