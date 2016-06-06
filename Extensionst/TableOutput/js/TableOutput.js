/**
 * Handles all the client functionality of the Onyl TableOutput extension.
 *
 * @param {object} el        The table output container, which has to have the class 'blend-box'.
 * @param {string} serverUrl The base url for ajax calls.
 *
 * @return {object} A new table output instance.
 */
// var TableOutput = function(el, serverUrl) {
//   var _this = this;
//   this.el = el;
//   this.serverUrl = serverUrl;
//   this.filter = {};
//   this.currentPage = 0;
//   this.pageSwitchTimer = null;
//   this.orderBy = null;
//   this.selectedRows = {};
//   this.singlePage = $(el).find('table').hasClass('single-page');
//
//   this.blendBox = new BlendBox(this, 1);
//   $('body').append(this.blendBox);

  // table output identifier
  // this.tableOutputId = $(this.el).find('.table-output').attr('data-table-output-id');

  // if (!this.singlePage) {
  //   $(this.el).find('.nav-btn').eq(0).addClass('curr');
  //   this.scrollToNavBtn(false);
  // }

  // get filter configuration
  // $.post(this.serverUrl + "get_filter_definitions", {
  //   id: this.tableOutputId
  // }, function(data) {
  //   _this.filter = JSON.parse(data);
  //   // set highlight for active filter
  //   for (var filter in _this.filter) {
  //     if (filter.apply === 0) continue;
  //     $(_this.el).find('.sticky[data-name="' + filter + '"]').find('.filter').addClass('active');
  //   }
  // });

  // clicked on checkbox
  // $(this.el).on('click', '.checkbox', function(evt) {
  //   if ($(_this.el).find('.checkbox[data-checked=true]').length === 0) {
  //     $(_this.el).find('.delete-all-link').fadeOut();
  //     return;
  //   }
  //   $(_this.el).find('.delete-all-link').fadeIn();
  // });

  // clicked on the delete button inside page actions
  // $(_this.el).on('click', '.delete-btn', function() {
  //   var rowArray = [];
  //   if (Object.keys(_this.selectedRows).length === 0) return;
  //   for (var row in _this.selectedRows) {
  //     rowArray.push(row);
  //   }
  //   _this.showDeleteAllBlendBox(rowArray);
  // });
  // clicked on the edit button inside page actions
  // $(this.el).on('click', '.edit-btn', function(evt) {
  //   evt.stopPropagation();
  //   evt.preventDefault();
  //   if (Object.keys(_this.selectedRows).length != 1) return;
  //   _this.showEditBlendBox($(_this.el).find('tr[data-row-id="' + Object.keys(_this.selectedRows)[0] + '"]'));
  // });
  // clicked on header
  // $(this.el).on('click', '.sticky', function() {
  //   var name = $(this).attr('data-name');
  //
  //   // ease
  //   $(_this.el).find('.sticky[data-order-by=true]').attr('data-order-by', 'false');
  //   _this.selectedRows = {};
  //   _this.adjustPageActions();
  //   if (_this.orderBy == name) delete _this.orderBy;
  //   else {
  //     _this.orderBy = name;
  //     $(_this.el).find('.sticky[data-name=' + name + ']').attr('data-order-by', 'true');
  //   }
  //   _this.loadPage(_this.currentPage, _this.orderBy, function() {
  //     _this.loadPageButtons();
  //   });
  // });

  // clicked on a table row
  // $(this.el).on('click', 'tbody tr', function() {
  //   var rowId = $(this).attr('data-row-id');
  //   if (_this.selectedRows[rowId]) {
  //     $(this).removeClass('selected');
  //     delete _this.selectedRows[rowId];
  //   } else {
  //     $(this).addClass('selected');
  //     _this.selectedRows[rowId] = 1;
  //   }
  //   _this.adjustPageActions();
  // });

  // clicked on prev button
  // $(this.el).on('click', '.prev-btn', function() {
  //   if (_this.currentPage === 0) return;
  //   _this.currentPage--;
  //   _this.loadPage(_this.currentPage, _this.orderBy);
  // });

  // // clicked on next button
  // $(this.el).on('click', '.next-btn', function() {
  //   var navBtns = $(_this.el).find('.nav-btn');
  //   if ($(navBtns).length == _this.currentPage + 1) return;
  //   _this.currentPage++;
  //   _this.loadPage(_this.currentPage, _this.orderBy);
  // });

  // clicked on a number button
  // $(this.el).on('click', '.nav-btn', function() {
  //   var index = $(this).index();
  //   if (_this.currentPage == index) return;
  //   _this.currentPage = index;
  //   _this.loadPage(_this.currentPage, _this.orderBy);
  // });

  // clicked on the filter button of a column
  // $(this.el).on('click', '.filter', function(evt) {
  //   var name = $(this).parent().attr('data-name');
  //   evt.stopPropagation();
  //   evt.preventDefault();
  //   if (_this.filter[name] && _this.filter[name].apply) {
  //     _this.filter[name].apply = 0;
  //     $(this).removeClass('active');
  //     _this.loadPage(_this.currentPage, _this.orderBy);
  //     return;
  //   }
  //   _this.showFilterBlendBox($(this).parent());
  // });

  // clicked on the new button
  // $(this.el).parent().on('click', '.new-btn', function() {
  //   _this.showNewBlendBox();
  // });
// };
// TableOutput.prototype = {
  // delete: function(rowId, complete) {
  //   $.post(this.serverUrl + "delete", {
  //     id: this.tableOutputId,
  //     rowId: rowId
  //   }, complete);
  // },
  // loadPageButtons: function() {
  //   var _this = this;
  //   $.post(this.serverUrl + "get_page_buttons", {
  //     id: this.tableOutputId
  //   }, function(data) {
  //     data = JSON.parse(data);
  //     $(_this.el).find('.overflow-btns').html(data.buttons);
  //     $(_this.el).find('.total-count .count').html(data.total);
  //     _this.updateActivePageButton();
  //   });
  // },
  // adjustPageActions: function() {
  //   var selRowCount = Object.keys(this.selectedRows).length,
  //     pageActionsContainer = $(this.el).find('.page-actions-container');
  //   if (selRowCount === 0) {
  //     pageActionsContainer.attr('data-visible', 'false');
  //     return;
  //   }
  //   pageActionsContainer.attr('data-visible', 'true');
  //   if (selRowCount == 1) {
  //     pageActionsContainer.find('.edit-btn').attr('data-visible', 'true');
  //     return;
  //   }
  //   pageActionsContainer.find('.edit-btn').attr('data-visible', 'false');
  // },
  // scrollToNavBtn: function(animation) {
  //   var overflowContainer = $(this.el).find('.overflow-btns'),
  //     navBtn = overflowContainer.find('.nav-btn.curr');
  //   if (navBtn.length === 0) return;
  //   if (animation) overflowContainer.stop().animate({
  //     scrollLeft: (navBtn.position().left + overflowContainer.scrollLeft() - overflowContainer.width() / 2) + "px"
  //   }, 200);
  //   else overflowContainer.scrollLeft((navBtn.position().left - overflowContainer.width() / 2) + "px");
  // },
  // updateActivePageButton: function() {
  //   navBtns = $(this.el).find('.nav-btn');
  //   $(this.el).find('.nav-btn.curr').removeClass('curr');
  //   $(navBtns).eq(this.currentPage).addClass('curr');
  // },
  // insert: function(id, values, complete) {
  //   $.post(this.serverUrl + "insert", {
  //     id: id,
  //     values: values
  //   }, complete);
  // },
  // edit: function(rowId, values, complete) {
  //   $.post(this.serverUrl + "edit", {
  //     id: this.tableOutputId,
  //     rowId: rowId,
  //     values: values
  //   }, complete);
  // },
  // showFilterBlendBox: function(el) {
  //   var name = $(el).attr('data-name'),
  //     displayName = $(el).attr('data-display-name'),
  //     type = $(el).attr('data-type'),
  //     fields = [],
  //     _this = this;
  //   $.post(this.serverUrl + "get_filter", {
  //     id: this.tableOutputId,
  //     field: name
  //   }, function(fields) {
  //     fields = JSON.parse(fields);
  //     if (_this.filter[name]) {
  //       fields.map(function(field) {
  //         if (!_this.filter[name][field.name]) return;
  //         field.value = _this.filter[name][field.name];
  //       });
  //     }
  //     fields.push({
  //       "type": "submit",
  //       "caption": "&Uuml;bernehmen",
  //       "name": "save"
  //     });
  //     _this.blendBox.open('Filter f&uuml;r ' + displayName, {
  //       fields: fields,
  //       submit: function(data) {
  //         _this.filter[name] = data;
  //         _this.filter[name].apply = 1;
  //         $(el).find('.filter').addClass('active');
  //         _this.blendBox.close();
  //         _this.loadPage(_this.currentPage, _this.orderBy);
  //       }
  //     });
  //   });
  // },
  /*
  show a box, used to create a new dataset
  */
  // showNewBlendBox: function() {
  //   var _this = this;
  //   $('.blend-box-summary').html(' ');
  //
  //   // get fields
  //   $.post(this.serverUrl + "get_new_fields", {
  //     id: this.tableOutputId
  //   }, function(data) {
  //     data = JSON.parse(data);
  //     data.push({
  //       type: 'submit',
  //       caption: 'Speichern'
  //     });
  //     _this.blendBox.open('Neuer Eintrag', {
  //       fields: data,
  //       submit: function(data) {
  //         _this.insert(_this.tableOutputId, data, function(data) {
  //           data = JSON.parse(data);
  //           // successfully inserted row
  //           if (data.code === 0) {
  //             _this.blendBox.success(data.msg);
  //             _this.loadPage(_this.currentPage);
  //             return;
  //           }
  //           // an error occured
  //           _this.blendBox.error(data.msg);
  //         });
  //       }
  //     });
  //   });
  // },
  /*
  show a box, used to edit an existing dataset
  */
  // showEditBlendBox: function(row) {
  //   var _this = this;
  //   $('.blend-box-summary').html('');
  //
  //   // get fields
  //   $.post(this.serverUrl + "get_new_fields", {
  //     id: this.tableOutputId
  //   }, function(data) {
  //     data = JSON.parse(data);
  //     data.push({
  //       type: 'submit',
  //       caption: 'speichern'
  //     });
  //
  //     // loop fields and insert data, that is already provided
  //     data.map(function(el) {
  //       var tblEl = $(row).find('[data-name=' + el.name + ']');
  //       el.value = $(tblEl).attr('data-value');
  //       if (!$(tblEl).is('[data-content]')) return;
  //       el.content = $(tblEl).attr('data-content');
  //     });
  //
  //     _this.blendBox.open('Eintrag Bearbeiten', {
  //       fields: data,
  //       submit: function(data) {
  //         _this.edit($(row).attr('data-row-id'), data, function(data) {
  //           data = JSON.parse(data);
  //           // an error occured
  //           if (data.code == 1) {
  //             _this.blendBox.error(data.msg);
  //             return;
  //           }
  //           // successfully updated row
  //           _this.blendBox.success(data.msg);
  //           setTimeout(function() {
  //             _this.blendBox.close();
  //           }, 400);
  //           _this.loadPage(_this.currentPage);
  //         });
  //       }
  //     });
  //   });
  // },

  /**
   * Displays a warning, whether the seleted entries should be deleted.
   *
   * @param {objcet} rowIds An object with properties, name after the rows to delete.
   *
   * @return {bool} Whether the notification was displayed.
   */
  // showDeleteAllBlendBox: function(rowIds) {
  //   var _this = this,
  //     title = rowIds.length > 1 ? TABLE_OUTPUT_DELETE_ENTRIES : TABLE_OUTPUT_DELETE_ENTRY,
  //     fields = [{
  //       type: 'submit',
  //       caption: TABLE_OUTPUT_ACCEPT_DELETE
  //     }];
  //   _this.blendBox.open(title, {
  //     fields: fields,
  //     submit: function(data) {
  //       var i = 0;
  //
  //       function del(data) {
  //         i++;
  //         if (i < Object.keys(rowIds).length) return;
  //         _this.blendBox.close();
  //         _this.loadPage(_this.currentPage);
  //       }
  //       for (var prop in rowIds) {
  //         _this.delete(rowIds[prop], del);
  //       }
  //       rowIds = {};
  //     }
  //   });
  //   return true;
  // }
// };
