API = (function () {
	
// @see http://docs.sencha.com/extjs/3.4.0/	

var createItem = function () {
	console.log('creating item - scope:', this);
	
	// Show editor UI
	// On save call out to server
	// On success update components and refresh the search
	
	var oEditor = new ItemEditor();
	oEditor.show();
}	

var editItem = function() {
	console.log('editing item - scope:', this);
	
	// Show editor UI based on selected record 
	// or call out to server for full payload and show upon that response
	
	var oRec = this.getSelectionModel().getSelected();
	if ( ! oRec ) return;
	
	var oEditor = new ItemEditor();
	oEditor.setData(oRec.data);
	oEditor.show();

}

var deleteItem = function () {
	console.log('delete item - scope:', this)
	
	// Gather ID(s) from grid selections using selection model
	// Call out to server to remove item(s) 
	// on success refresh search or remove record(s) from grid
	
	this.executeSearch();
}	

var ItemEditor = Ext.extend(Ext.Window, {
	setData: function ( oData ) {
		this.get('formpanel').getForm().setValues(oData);		
	},
	
	initComponent: function () {
		this.title = 'Item editor';
		this.width = 450;
		this.layout = 'form';
		this.autoHeight = true;
		this.closable = true;
		this.modal = true;
		
		this.buttons = [{
			text: 'Save'
		}, {
			text: 'Close'			
		}];
		
		this.items = {
			xtype: 'form',
			itemId: 'formpanel',
			layout: 'form',
			labelAlign: 'top',
			border: false,
			defaults: { anchor: '100%' },
			items: [{
				xtype: 'textfield',
				fieldLabel: 'Title',
				name: 'title',
				allowBlank: false,
				emptyText: 'Title'
			}, {				
				xtype: 'textarea',
				fieldLabel: 'Summary',				
				name: 'summary',
				title: 'Summary'
			}]
		};
		
		ItemEditor.superclass.initComponent.call(this);
	}
});

/* {{{ Results Grid */

var ResultsPanel = Ext.extend(Ext.grid.GridPanel, { 
	executeSearch: function ( oParams ) { 			
		this.getStore().load({ params: oParams });
	},
	
	initComponent: function () {			
		this.tbar = [{
			text: 'New',
			itemId: 'new',
			iconCls: 'tncms-icons-create',
			handler: createItem,
			scope: this
		}, '->', {
			text: 'Edit',
			itemId: 'edit',
			iconCls: 'tncms-icons-edit',
			handler: editItem,
			scope: this
		}, '-', {
			text: 'Delete',
			itemId: 'delete',
			iconCls: 'tncms-icons-remove',
			handler: deleteItem,
			scope: this
		}];
		
		this.viewConfig = {
			deferEmptyText: false,
			emptyText: 'No items found'
		};
		
		var oSM = new Ext.grid.CheckboxSelectionModel();
		this.sm = oSM;
		
		this.columns = [ oSM, {
			id: 'id',
			dataIndex: 'id',
			header: 'ID',
			editable: false,
			hidden: true,
			width: 100
		}, {
			id: 'title',
			dataIndex: 'title',
			header: 'Title',
			editable: false,
			width: 200,
		}, {
			id: 'summary',
			dataIndex: 'summary',
			header: 'Summary',
			editable: false,
			width: 300,				
		}];
		
		this.autoExpandColumn = 'summary';
		
		this.store = {
			xtype: 'jsonstore',
			url: TNCMS.actionURL('example/main/search'),
			autoLoad: false,
			idProperty: 'id',
			root: 'data',
			fields: [ 'id', 'title', 'summary' ],
			listeners: {
				beforeload: function () {
					this.getEl().mask('Loading');
				},
				load: function () {
					this.getEl().unmask();
				},
				exception: function () {
					this.getEl().unmask();
				},
				scope: this
			}
		};
		
		this.listeners = {
			rowdblclick: editItem,
			scope: this
		};
		
		TNCMS.Broadcast.listen(
			'example', 'main', 'search', this.executeSearch, this
		);
		
		ResultsPanel.superclass.initComponent.call(this);
	}
});

/* }}} */
/* {{{ SearchPanel */

var SearchPanel = Ext.extend(Ext.Panel, { 
	search: function () { 
		// Gather search criteria from form
		// May require more than just getValues depending on complexity
		var oSearch = this.get(0).getForm().getValues();
		// Fire event for results panel to execute
		TNCMS.Broadcast.fire('example', 'main', 'search', oSearch);
	},
	
	reset: function () {
		// Reset form elements to default state
		// May require more than just calling reset if the form is complex
		this.get(0).getForm().reset();
		// Execute search with defaults
		this.search();
	},
	
	initComponent: function () {
		this.layout = 'form';
		this.title = 'Search terms';
		
		this.tbar = ['->', {
			text: 'Reset',
			iconCls: 'tncms-icons-reload',
			handler: this.reset,
			scope: this
		}, '-', {
			text: 'Search',
			iconCls: 'tncms-icons-search',
			handler: this.search,
			scope: this
		}];
		
		this.items = {
			xtype: 'form',
			layout: 'form',
			labelAlign: 'top',
			border: false,
			defaults: { anchor: '100%' },
			items: [{
				xtype: 'textfield',
				name: 'query',
				fieldLabel: 'Search text'
			}]
		};
		
		SearchPanel.superclass.initComponent.call(this);
	}
});

/* }}} */
/* {{{ Public API method calls */

return {
	
	/* {{{ resultsPanel */
	
	/**
	 * 
	 */
	resultsPanel: ResultsPanel,
	
	/* }}} */
	/* {{{ searchPanel */
	
	searchPanel: SearchPanel,
	
	/* }}} */
}

/* }}} */
	
})();