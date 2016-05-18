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
    this.highlightActiveFilters();
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

    // clicked on the delete button
    // $(this.tableOutput).on('click', '.delete-btn', function() {
    //   var rowArray = [];
    //
    //   // no rows are selected
    //   if (Object.keys(_this.selectedRows).length === 0) return;
    //
    //   // loop selected rows
    //   for (var row in _this.selectedRows) {
    //     rowArray.push(row);
    //   }
    //
    //   // ask the user if he wants to delete the selected rows
    //   _this.showDeleteBlendBox(rowArray);
    // });

    // clicked on the edit button
    // $(this.tableOutput).on('click', '.edit-btn', function(evt) {
    //   evt.stopPropagation();
    //   evt.preventDefault();
    //
    //   // exactly one row has to be selected to edit
    //   if (Object.keys(_this.selectedRows).length != 1) return;
    //
    //   // show edit blend-box
    //   _this.showEditBlendBox($(_this.tableOutput).find('tr[data-row-id="' + Object.keys(_this.selectedRows)[0] + '"]'));
    // });

    // clicked on a row
    // $(this.tableOutput).on('click', '.row', function() {
    //   var record = _this.getRecordById($(this).attr('data-row-id'));
    //
    //   console.log(record)
    //
    //   record.checked = !record.checked;
    //
    //   _this.tableOutput.updateHasSelection();
    // });

    // clicked on previous button
    // $(this.tableOutput).on('click', '.prev-btn', function() {
    //
    //   // the current page is the first page
    //   if (_this.currentPage === 0) return;
    //
    //   _this.currentPage--;
    //
    //   // load page
    //   _this.loadPage(_this.currentPage, _this.orderBy);
    // });

    // clicked on next button
    // $(this.tableOutput).on('click', '.next-btn', function() {
    //   var navBtns = $(_this.tableOutput).find('.nav-btn');
    //
    //   // the current page is the last page
    //   if ($(navBtns).length == _this.currentPage + 1) return;
    //
    //   _this.currentPage++;
    //
    //   // load page
    //   _this.loadPage(_this.currentPage, _this.orderBy);
    // });

    // clicked on a header nav entry
    // $(this.tableOutput).on('click', '.nav-btn', function() {
    //   var index = $(this).index();
    //
    //   // already on this page
    //   if (_this.currentPage == index) return;
    //
    //   _this.currentPage = index;
    //
    //   // load page
    //   _this.loadPage(_this.currentPage, _this.orderBy);
    // });

    // clicked on the filter button of a field
    // $(this.tableOutput).on('click', '.filter', function(evt) {
    //   var index = $(this).parent().index(),
    //       field = _this.tableOutput.structure[index];
    //
    //   // this field has an active filter
    //   if (field.filter.isApplied) {
    //     filter.filter.isApplied = false;
    //
    //     // _this.loadPage(_this.currentPage, _this.orderBy);
    //     return;
    //   }
    //
    //   // set this filter
    //   // _this.showFilterBlendBox(field);
    // });
  },

  /**
   * Get data record by id.
   * @param int id the id
   * @return Array the record
   */
  getRecordById: function(id) {
    var records = this.tableOutput.records;
    for (var i=0; i<records.length; i++) {
      if (records[i].id == id) return records[i];
    }
  },

  /**
   * Highlight the active filters.
   * @return void
   */
  highlightActiveFilters: function() {

    // loop through filters
    for (var filter in this.filter) {

      // this filter is disabled
      if (filter.apply === 0) continue;

      // mark filter as active
      $(this.tableOutput).find('.sticky[data-name="' + filter + '"]').find('.filter').addClass('active');
    }
  },

  /**
   * Scroll to the active header nav button.
   * @param  boolean animation scroll with an easing animation
   * @return void
   */
  // scrollToNavBtn: function(animation) {
  //   var overflowContainer = $(this.el).find('.overflow-btns'),
  //       navBtn = overflowContainer.find('.nav-btn.curr');
  //
  //   // could not find an active nav button
  //   if (navBtn.length === 0) return;
  //
  //   // stop the ongoing animation and animate to the nav button
  //   if (animation) overflowContainer.stop().animate({
  //     scrollLeft: (navBtn.position().left + overflowContainer.scrollLeft() - overflowContainer.width() / 2) + "px"
  //   }, 200);
  //
  //   // focus the nav button without an animation
  //   else overflowContainer.scrollLeft((navBtn.position().left - overflowContainer.width() / 2) + "px");
  // },

  /**
   * Set the active header nav button.
   * @return void
   */
  updateActiveNavButton: function() {
    pageButtons = this.tableOutput.pageButtons;

    for (var i=0; i<pageButtons.length; i++) {
      this.tableOutput.set(sprintf('pageButtons.%s.current', i), i == this.currentPage);
    }
  },

  /**
   * Update order by status of a field.
   * @param  string name    the field name
   * @return void
   */
  updateOrderBy: function() {
    var _this = this;

    // send new order by to the server
    $.post(this.serverUrl + "set_order_by", {
      id: this.tableOutputId,
      orderBy: this.tableOutput.orderBy,
      orderByReversed: this.tableOutput.isOrderByReversed
    }, function(data) {
      data = JSON.parse(data);

      // update successful
      if (data.code == 0) {

        // reload the page
        _this.loadPage(_this.currentPage, function() {

          // reload page buttons
          _this.loadPageButtons();
        });
      }
    });
  },

  /**
   * Load the new page buttons.
   * @return void
   */
  loadPageButtons: function() {
    var _this = this;

    // get the page buttons from the server
    $.post(this.serverUrl + "get_page_buttons", {
      id: this.tableOutputId
    }, function(data) {
      data = JSON.parse(data);

      // update page buttons
      _this.tableOutput.updateNavButtons(data.buttons);

      // set the active page button
      _this.updateActiveNavButton();
    });
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
        fields = this.tableOutput.structure;

    for (var i in fields) {
      fields[i].content = recordFields[i].content;
      fields[i].value = recordFields[i].value;
    }

    this.tableOutput.$.blendBox.open(TABLE_OUTPUT_EDIT_ENTRY, {
      fields: fields,
      submit: function(data, success) {
        _this.edit(row.id, data, function(data) {
          data = JSON.parse(data);

          // successfully inserted row
          if (data.code === 0) {
            success(data.msg);
            _this.loadPage(_this.currentPage);
            success();
            return;
          }

          // an error occured
          _this.blendBox.error(data.msg);
        });
      }
    });

    // $.post(this.serverUrl + "get_new_fields", {
    //   id: this.tableOutputId
    // }, function(data) {
    //   data = JSON.parse(data);
    //   data.push({
    //     type: 'submit',
    //     caption: 'speichern'
    //   });
    //
    //   // loop fields and insert data, that is already provided
    //   data.map(function(el) {
    //     var tblEl = $(row).find('[data-name=' + el.name + ']');
    //     el.value = $(tblEl).attr('data-value');
    //     if (!$(tblEl).is('[data-content]')) return;
    //     el.content = $(tblEl).attr('data-content');
    //   });
    //
    //   _this.blendBox.open('Eintrag Bearbeiten', {
    //     fields: data,
    //     submit: function(data) {
    //       _this.edit($(row).attr('data-row-id'), data, function(data) {
    //         data = JSON.parse(data);
    //
    //         // an error occured
    //         if (data.code == 1) {
    //           _this.blendBox.error(data.msg);
    //           return;
    //         }
    //
    //         // successfully updated data
    //         _this.blendBox.success(data.msg);
    //         setTimeout(function() {
    //           _this.blendBox.close();
    //         }, 400);
    //
    //         _this.loadPage(_this.currentPage);
    //       });
    //     }
    //   });
    // });
  },

  /**
   * Displays a blend-box for creating a new dataset.
   * @return void
   */
  showNewBlendBox: function() {
    var _this = this;

    this.tableOutput.$.blendBox.open('Neuer Eintrag', {
      fields: this.tableOutput.structure,
      submit: function(data, success) {
        _this.insert(_this.tableOutputId, data, function(data) {
          data = JSON.parse(data);

          // successfully inserted row
          if (data.code === 0) {
            success(data.msg);
            _this.loadPage(_this.currentPage);
            success();
            return;
          }

          // an error occured
          _this.tableOutput.$.blendBox.error(data.msg);
        });
      }
    });

    // get fields
    // $.post(this.serverUrl + "get_new_fields", {
    //   id: this.tableOutputId
    // }, function(data) {
    //   data = JSON.parse(data);
    //   data.push({
    //     type: 'submit',
    //     caption: 'Speichern'
    //   });
    //   _this.blendBox.open('Neuer Eintrag', {
    //     fields: data,
    //     submit: function(data) {
    //       _this.insert(_this.tableOutputId, data, function(data) {
    //         data = JSON.parse(data);
    //         // successfully inserted row
    //         if (data.code === 0) {
    //           _this.blendBox.success(data.msg);
    //           _this.loadPage(_this.currentPage);
    //           return;
    //         }
    //         // an error occured
    //         _this.blendBox.error(data.msg);
    //       });
    //     }
    //   });
    // });
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
          i++;
          if (i < entries.length) return;
          success();
          _this.loadPage(_this.currentPage);
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
  showFilterBlendBox: function(field, attach) {
    var displayName = field.header,
      type = field.type,
      fields = [],
      _this = this;

    // show filter blend-box
    this.tableOutput.$.blendBox.open(sprintf(TABLE_OUTPUT_FILTER_FOR, displayName), {
      fields: field.filter.structure,
      submit: function(data, success) {

        // send the new filter values to the server
        $.post(_this.serverUrl + 'set_filter', {
          id: _this.tableOutputId,
          fieldName: field.name,
          filterValues: {
            fields: data,
            isApplied: true
          },
        }, function(data) {
          data = JSON.parse(data);

          // no errors occurred when setting the filter values
          if (data.code === 0) {

            // the filter was successfully attached
            attach();

            // close the blend-box
            success();
          }
        });
      }
    });
  },

  /**
   * Detach an active filter.
   */
  removeFilter: function(field, detach) {
    $.post(this.serverUrl + 'set_filter', {
      id: this.tableOutputId,
      fieldName: field.name,
      filterValues: {
        fields: [],
        isApplied: false
      }
    }, function(data) {

      // the filter was detached
      detach();
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
  loadPage: function(page, success) {
    var _this = this,
      navBtns,
      data = { id: this.tableOutputId, startPage: page };

    // display loading animation
    this.tableOutput.isLoading = true;

    this.selectedRows = {};
    this.adjustPageActions();

    // update current page
    this.currentPage = page;

    // set active nav button
    this.updateActiveNavButton();

    clearTimeout(this.pageSwitchingTimer);
    this.pageSwitchingTimer = setTimeout(function() {

      // get the page from server
      $.post(_this.serverUrl + "get_records", data, function(data) {
        data = JSON.parse(data);

        // update table data
        _this.tableOutput.updateRecords(data);

        // update has selection
        _this.tableOutput.updateHasSelection();

        // hide loading animation
        _this.tableOutput.isLoading = false;

        if (success) success();
      });
    }, 400);
  },

  /**
   * Insert a new dataset.
   * @param  int      id        tableOutputId
   * @param  Object   values    the new values
   * @param  function complete  success handler
   * @return void
   */
  insert: function(id, values, complete) {
    $.post(this.serverUrl + "insert", {
      id: id,
      values: values
    }, complete);
  },

  /**
   * Edit a dataset.
   * @param  int      rowId     the row id
   * @param  function complete  success handler
   * @return void
   */
  delete: function(rowId, complete) {
    $.post(this.serverUrl + "delete", {
      id: this.tableOutputId,
      rowId: rowId
    }, complete);
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
      values: values
    }, complete);
  },
};
