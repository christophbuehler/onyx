var TableOutputHandler = function(tableOutput, serverUrl) {
  this.tableOutputId = tableOutput.id;
  this.tableOutput = tableOutput;
  this.currentPage = 0;
  this.singlePage = tableOutput.singlePage;
  this.filter = tableOutput.filter;
  this.selectedRows = {};
  this.serverUrl = serverUrl;
  this.pageSwitchingTimer = {};
  this.init();
};
TableOutputHandler.prototype = {

  /**
   * Initialize components.
   * @return void
   */
  init: function() {
    this.initListeners();
    // this.highlightActiveFilters();
  },

  /**
   * Initialize DOM listeners.
   * @return void
   */
  initListeners: function() {
    var _this = this;

    // clicked on the checkbox of a record
    $(this.tableOutput).on('click', '.checkbox', function(evt) {

      // no checkbox is active
      if ($(_this.tableOutput).find('.checkbox[data-checked=true]').length === 0) {

        // hide delete button
        $(_this.el).find('.delete-btn').fadeOut();
        return;
      }

      // show delete button
      $(_this.el).find('.delete-btn').fadeIn();
    });
  },

  /**
   * Get data record by id.
   * @param int id the id
   * @return Array the record
   */
  getRecordById: function(id) {
    var records = this.tableOutput.records;
    for (var i = 0; i < records.length; i++) {
      if (records[i].id == id) return records[i];
    }
  },
  /**
   * Highlight the active filters.
   * @return void
   */
  /*highlightActiveFilters: function() {

    // loop through filters
    for (var filter in this.filter) {

      // this filter is disabled
      if (filter.apply === 0) continue;

      // mark filter as active
      $(this.tableOutput).find('.sticky[data-name="' + filter + '"]').find('.filter').addClass('active');
    }
  },*/

  /**
   * Set the active header nav button.
   * @return void
   */
  updateActiveNavButton: function() {
    pageButtons = this.tableOutput.pageButtons;
    for (var i = 0; i < pageButtons.length; i++) {
      this.tableOutput.set(sprintf('pageButtons.%s.current', i), i == this.currentPage);
    }
  },

  /**
   * Load the new page buttons.
   * @return void
   */
  displayPageButtons: function(data) {

    // update page buttons
    this.tableOutput.updateNavButtons(data.buttons);

    // set the active page button
    this.updateActiveNavButton();
  },

  cloneObject: function(obj) {
    var n = {};
    for (var i in obj) n[i] = obj[i];
    return n;
  },

  /**
   * Clone the structure.
   *
   * @return array fields
   */
  cloneStructure: function() {
    var fields = [],
        structure = this.tableOutput.structure;

    // clone the structure and
    // insert values and contents
    for (var i in structure) {
      fields.push(this.cloneObject(structure[i]));
    }

    return fields;
  },

  /**
   * Displays a blend-box for editing an existing dataset.
   * @param  Object row the HTML row to edit
   * @return void
   */
  showEditBlendBox: function() {
    var _this = this,
      row = this.tableOutput.records.filter(function(record) {
        return record.checked;
      })[0],
      recordFields = row.fields,
      fields = this.cloneStructure();

    // insert values and contents
    for (var i in fields) {
      fields[i].content = recordFields[i].content;
      fields[i].value = recordFields[i].value;
    }

    this.tableOutput.$.blendBox.open(TABLE_OUTPUT_EDIT_ENTRY, {
      fields: fields,
      submit: function(data, success) {
        _this.edit(row.id, data, function(data) {
          data = utils.parseResponse(data);

          // successfully inserted row
          if (data.code === 0) {
            success(data.msg);
            _this.loadPage();
            success();
            return;
          }

          // an error occured
          _this.tableOutput.$.blendBox.error(data.msg);
        });
      }
    });
  },

  /**
   * Displays a blend-box for creating a new dataset.
   * @return void
   */
  showNewBlendBox: function() {
    var _this = this;
    this.tableOutput.$.blendBox.open('Neuer Eintrag', {
      fields: this.cloneStructure(),
      submit: function(data, success) {

        _this.insert(_this.serverUrl, _this.tableOutputId, data, function(data) {
          data = utils.parseResponse(data);

          // successfully inserted row
          if (data.code != 'undefined' && data.code === 0) {
            success(data.msg);
            _this.loadPage();
            success();
            return;
          }

          // an error occured
          _this.tableOutput.$.blendBox.error(data.msg || data);
        });
      }
    });
  },

  /**
   * Displays a warning, whether the seleted entries should be deleted.
   * @param  Array rowIds a list of rows to be deleted
   * @return void
   */
  showDeleteBlendBox: function() {
    var _this = this,
      entries = this.tableOutput.records.filter(function(entry) {
        return entry.checked;
      }),
      title = entries.length > 1 ? TABLE_OUTPUT_DELETE_ENTRIES : TABLE_OUTPUT_DELETE_ENTRY,
      fields = [];

    // open blend-box
    this.tableOutput.$.blendBox.open(title, {
      fields: fields,
      submit: function(data, success) {
        var i = 0;

        function del(data) {
          data = utils.parseResponse(data);

          // successfully deleted entry
          if (data.code === 0) {
            i++;
            if (i < entries.length) return;
            success();
            _this.loadPage();
            return;
          }

          // an error occured
          _this.tableOutput.$.blendBox.error(data.msg);
        }

        entries.map(function(entry) {
          entry.checked = false;
          _this.delete(entry.id, del);
        });
      }
    });
    return true;
  },

  /**
   * Display the blend-box for a filter.
   * @param  {[type]} el [description]
   * @return {[type]}    [description]
   */
  showFilterBlendBox: function(field, path, attach) {
    var displayName = field.header,
      type = field.type,
      fields = [],
      _this = this;

    // show filter blend-box
    this.tableOutput.$.blendBox.open(sprintf(TABLE_OUTPUT_FILTER_FOR, displayName), {
      fields: field.filter.structure,
      submit: function(data, success) {

        if (_this.filter[path] && _this.filter[path].isApplied) {
          _this.filter[path] = {
            fields: data,
            isApplied: false
          };
        } else {
          _this.filter[path] = {
            fields: data,
            isApplied: true
          };
          attach();
          success();
        }
      }
    });
  },
  /**
   * Adjust page actions container.
   * @return void
   */
  adjustPageActions: function() {
    var selRowCount = Object.keys(this.selectedRows).length,
      pageActionsContainer = $(this.tableOutput).find('.page-actions-container');
    // no selected rows
    if (selRowCount === 0) {
      // hide page actions
      pageActionsContainer.attr('data-visible', 'false');
      return;
    }
    // show page actions
    pageActionsContainer.attr('data-visible', 'true');
    // one row is selected
    if (selRowCount == 1) {
      // show edit button
      pageActionsContainer.find('.edit-btn').attr('data-visible', 'true');
      return;
    }
    // hide edit button, because more than one row is selected
    pageActionsContainer.find('.edit-btn').attr('data-visible', 'false');
  },
  /**
   * Load a table-output page.
   * @param  int      page    the page number
   * @param  string   orderBy order by string
   * @param  function success success handler
   * @return void
   */
  loadPage: function(success) {
    var _this = this,
      navBtns,
      data = {
        id: this.tableOutputId,
        page: this.currentPage,
        orderBy: this.tableOutput.orderBy,
        orderByReversed: this.tableOutput.isOrderByReversed ? 1 : 0,
        filter: this.filter
      };

    // display loading animation
    this.tableOutput.isLoading = true;
    this.selectedRows = {};
    this.adjustPageActions();

    // set active nav button
    this.updateActiveNavButton();
    clearTimeout(this.pageSwitchingTimer);
    this.pageSwitchingTimer = setTimeout(function() {

      // get the page from server
      $.get(_this.serverUrl + "records", data, function(data) {
        data = utils.parseResponse(data);

        // update table data
        _this.tableOutput.updateRecords(data.records);

        _this.displayPageButtons(data.pageButtons);

        // update has selection
        _this.tableOutput.updateHasSelection();

        // hide loading animation
        _this.tableOutput.isLoading = false;
        if (success) success();
      });
    }, 400);
  },

  /**
   * Get the reverse link content.
   * @param  string url       the url
   * @param  string id        the table-output-id
   * @param  string fieldName the field name
   * @return void
   */
  getReverseLink: function(url, id, fieldName, value, success) {
    var _this = this;
    $.get(sprintf('%s/link?id=%s&f=%s&v=%s&reverse=true', url, id, fieldName, value), function(data) {
      success(utils.parseResponse(data));
    });
  },

  /**
   * Insert a new dataset.
   * @param  int      id        tableOutputId
   * @param  Object   values    the new values
   * @param  function complete  success handler
   * @return void
   */
  insert: function(url, id, values, success) {
    $.post(url + "insert", {
      id: id,
      values: values
    }, success);
  },

  /**
   * Edit a dataset.
   * @param  int      rowId     the row id
   * @param  function complete  success handler
   * @return void
   */
  delete: function(rowId, complete) {
    $.get(sprintf('%s/delete/?id=%s&rowId=%s', this.serverUrl, this.tableOutputId, rowId), complete);
  },

  /**
   * Edit an existing dataset.
   * @param  int      rowId     the row id
   * @param  Object   values    the new values
   * @param  function complete  success handler
   * @return void
   */
  edit: function(rowId, values, complete) {
    $.post(this.serverUrl + "edit", {
      id: this.tableOutputId,
      rowId: rowId,
      values: values,
    }, complete);
  }
};
