$(function() {
  var tableOutputs = [],
    hoverTable,
    serverUrl = '../tableOutput/';

  $('.table-output-bounding-box').each(function() {
    tableOutputs.push(new TableOutput(this, serverUrl));
  });

  $('.table-output-bounding-box').mouseenter(function() {
    hoverTable = this;
  }).mouseleave(function() {
    if (hoverTable != this) return;
    hoverTable = null;
  });
});

var TableOutput = function(el, serverUrl) {
  var _this = this;

  // table output filters
  this.filters = {};

  this.currentPage = 0;

  this.pageSwitchTimer = null;

  this.orderBy = null;

  this.selectedRows = {};

  // table output element
  this.el = el;

  this.singlePage = $(el).find('table').hasClass('single-page');

  // url for ajax calls
  this.serverUrl = serverUrl;

  // table output identifier
  this.tableOutputId = $(this.el).find('.table-output').attr('data-table-output-id');

  if (!this.singlePage) {
    $(this.el).find('.nav-btn').eq(0).addClass('curr');
    this.scrollToNavBtn(false);
  }

  // get filter configuration
  $.post(this.serverUrl + "get_filter_definitions", {
    tableOutputId: this.tableOutputId
  }, function(data) {
    _this.filters = JSON.parse(data);

    // set highlight for active filters
    for (var filter in _this.filters) {
      if (filter.apply === 0) continue;
      $(_this.el).find('.sticky[data-name="' + filter + '"]').find('.filter').attr('data-active', 'true');
    }
  });

  // clicked on checkbox
  $(this.el).on('click', '.checkbox', function(evt) {
    if ($(_this.el).find('.checkbox[data-checked=true]').length === 0) {
      $(_this.el).find('.delete-all-link').fadeOut();
      return;
    }
    $(_this.el).find('.delete-all-link').fadeIn();
  });

  // clicked on the delete button inside page actions
  $(_this.el).on('click', '.delete-btn', function() {
    var rowArray = [];


    if (Object.keys(_this.selectedRows).length === 0) return;

    for (var row in _this.selectedRows) {
      rowArray.push(row);
    }

    _this.showDeleteAllBlendBox(rowArray);
  });

  // clicked on the edit button inside page actions
  $(this.el).on('click', '.edit-btn', function(evt) {
    evt.stopPropagation();
    evt.preventDefault();

    if (Object.keys(_this.selectedRows).length != 1) return;

    _this.showEditBlendBox($(_this.el).find('tr[data-row-id="' + Object.keys(_this.selectedRows)[0] + '"]'));
  });

  // clicked on header
  $(this.el).on('click', '.sticky', function() {
    var name = $(this).attr('data-name');

    // ease
    $(_this.el).find('.sticky[data-order-by=true]').attr('data-order-by', 'false');

    if (_this.orderBy == name)
      delete _this.orderBy;
    else {
      _this.orderBy = name;
      $(_this.el).find('.sticky[data-name=' + name + ']').attr('data-order-by', 'true');
    }

    _this.loadPage(_this.currentPage, _this.orderBy, function() {
      _this.loadPageButtons();
    });
  });

  // clicked on a table row
  $(this.el).on('click', 'tr', function() {
    var rowId = $(this).attr('data-row-id');
    if (_this.selectedRows[rowId]) {
      $(this).attr('data-selected', 'false');
      delete _this.selectedRows[rowId];
    } else {
      $(this).attr('data-selected', 'true');
      _this.selectedRows[rowId] = 1;
    }
    _this.adjustPageActions();
  });

  // clicked on prev button
  $(this.el).on('click', '.prev-btn', function() {
    if (_this.currentPage === 0) return;

    _this.currentPage--;
    _this.loadPage(_this.currentPage, _this.orderBy);
  });

  // clicked on next button
  $(this.el).on('click', '.next-btn', function() {
    var navBtns = $(_this.el).find('.nav-btn');
    if ($(navBtns).length == _this.currentPage + 1) return;

    _this.currentPage++;
    _this.loadPage(_this.currentPage, _this.orderBy);
  });

  // clicked on a number button
  $(this.el).on('click', '.nav-btn', function() {
    var index = $(this).index();
    if (_this.currentPage == index) return;

    _this.currentPage = index;

    _this.loadPage(_this.currentPage, _this.orderBy);
  });

  // clicked on the filter button of a column
  $(this.el).on('click', '.filter', function(evt) {
    evt.stopPropagation();
    evt.preventDefault();

    _this.showFilterBlendBox($(this).parent());
  });

  // clicked on the new button
  $(this.el).parent().on('click', '.new-btn', function() {
    _this.showNewBlendBox();
  });

  // sticky table headers
  $(window).on('scroll', function(evt) {
    var scrl = $('html, body').scrollTop();

    _this.adjustHeight();

    if ($(_this.el).offset().top > scrl) {
      $(_this.el).attr('data-scroll', 'false');
      return;
    }

    $(_this.el).attr('data-scroll', 'true');
  });

  $(window).resize(function() {
    _this.adjustHeight();
  });

  this.adjustHeight();
};

TableOutput.prototype = {
  delete: function(rowId, complete) {
    $.post(this.serverUrl + "delete", {
      tableOutputId: this.tableOutputId,
      rowId: rowId
    }, complete);
  },

  adjustHeight: function() {
    var table = $(this.el).find('.table-output'),
      scroll = $('html, body').scrollTop(),
      height = $(window).height(),
      topOffset = table.offset().top,
      scrollBarHeight = 12;

    // this table is not visible
    if (topOffset > scroll + height) return;

    $(table).css('max-height', (scroll + height - topOffset - scrollBarHeight) + 'px');
  },

  loadPageButtons: function() {
    var _this = this;
    $.post(this.serverUrl + "get_page_buttons", {
      tableOutputId: this.tableOutputId
    }, function(data) {
      $(_this.el).find('.overflow-btns').html(JSON.parse(data));
    });
  },

  adjustPageActions: function() {
    var selRowCount = Object.keys(this.selectedRows).length,
      pageActionsContainer = $(this.el).find('.page-actions-container');

    if (selRowCount == 0) {
      pageActionsContainer.attr('data-visible', 'false');
      return;
    }
    pageActionsContainer.attr('data-visible', 'true');
    if (selRowCount == 1) {
      pageActionsContainer.find('.edit-btn').attr('data-visible', 'true');
      return;
    }
    pageActionsContainer.find('.edit-btn').attr('data-visible', 'false');
  },

  scrollToNavBtn: function(animation) {
    var overflowContainer = $(this.el).find('.overflow-btns'),
      navBtn = overflowContainer.find('.nav-btn.curr');

    if (navBtn.length === 0) return;

    if (animation)
      overflowContainer.stop().animate({
        scrollLeft: (navBtn.position().left + overflowContainer.scrollLeft() - overflowContainer.width() / 2) + "px"
      }, 200);
    else
      overflowContainer.scrollLeft((navBtn.position().left - overflowContainer.width() / 2) + "px");
  },

  loadPage: function(page, orderBy, success) {
    var _this = this,
      navBtns,
      data = {
        tableOutputId: this.tableOutputId,
        start: page * 20,
        limit: 20
      };

    this.selectedRows = {};
    this.adjustPageActions();

    if (orderBy)
      data.orderBy = orderBy;
    else
      data.orderBy = '';

    data.filter = _this.filters;

    data.printTableHead = false;
    data.printPageActions = false;

    // ease
    navBtns = $(_this.el).find('.nav-btn');
    $(_this.el).find('.nav-btn.curr').removeClass('curr');

    $(navBtns).eq(_this.currentPage).addClass('curr');

    _this.scrollToNavBtn(true);

    clearTimeout(this.pageSwitchingTimer);
    this.pageSwitchingTimer = setTimeout(function() {
      $.post(_this.serverUrl + "get_page", data, function(data) {

        data = JSON.parse(data);
        $(_this.el).find('.table-output-content tbody').html(data);

        if (success) success();
      });

    }, 400);
  },

  insert: function(values, complete) {
    $.post(this.serverUrl + "insert", {
      tableOutputId: this.tableOutputId,
      values: values
    }, complete);
  },

  edit: function(rowId, values, complete) {
    $.post(this.serverUrl + "edit", {
      tableOutputId: this.tableOutputId,
      rowId: rowId,
      values: values
    }, complete);
  },

  showFilterBlendBox: function(el) {
    var name = $(el).attr('data-name'),
      displayName = $(el).attr('data-display-name'),
      type = $(el).attr('data-type'),
      fields = [],
      _this = this;

    $.post(this.serverUrl + "get_filter", {
      tableOutputId: this.tableOutputId,
      field: name
    }, function(fields) {
      fields = JSON.parse(fields);

      // fields.push({ "type": "bool", "caption": "Filter anwenden", "name": "apply"});

      if (_this.filters[name]) {
        fields.map(function(field) {
          if (!_this.filters[name][field.name]) return;
          field.value = _this.filters[name][field.name];
        });
      }

      fields.push({
        "type": "submit",
        "caption": "&Uuml;bernehmen",
        "name": "save"
      });

      flatUi.blendBox.open('Filter f&uuml;r ' + displayName, {
        fields: fields,
        submit: function(data) {
          _this.filters[name] = data;
          $(el).find('.filter').attr('data-active', ~~data.apply ? 'true' : 'false');
          flatUi.blendBox.close();
          _this.loadPage(_this.currentPage, _this.orderBy);
        }
      });
    });
  },

  /*
  show a box, used to create a new dataset
  */
  showNewBlendBox: function() {
    var _this = this;

    $('.blend-box-summary').html(' ');

    // get fields
    $.post(this.serverUrl + "get_new_fields", {
      tableOutputId: this.tableOutputId
    }, function(data) {
      data = JSON.parse(data);
      data.push({
        type: 'submit',
        caption: 'Speichern'
      });

      flatUi.blendBox.open('Neuer Eintrag', {
        fields: data,
        submit: function(data) {
          _this.insert(data, function(data) {
            data = JSON.parse(data);

            // successfully inserted row
            if (data.code == 0) {
              flatUi.blendBox.success(data.msg);
              _this.loadPage(_this.currentPage);
              return;
            }

            // an error occured
            flatUi.blendBox.error(data.msg);
          });
        }
      });
    });
  },

  /*
  show a box, used to edit an existing dataset
  */
  showEditBlendBox: function(row) {
    var _this = this;

    $('.blend-box-summary').html('');

    // get fields
    $.post(this.serverUrl + "get_new_fields", {
      tableOutputId: this.tableOutputId
    }, function(data) {
      data = JSON.parse(data);
      data.push({
        type: 'submit',
        caption: 'speichern'
      });

      // loop fields and insert data, that is already provided
      data.map(function(el) {
        var tblEl = $(row).find('[data-name=' + el.name + ']');
        el.value = $(tblEl).attr('data-value');

        if (!$(tblEl).is('[data-content]')) return;
        el.content = $(tblEl).attr('data-content');
      });

      flatUi.blendBox.open('Eintrag Bearbeiten', {
        fields: data,
        submit: function(data) {
          _this.edit($(row).attr('data-row-id'), data, function(data) {
            data = JSON.parse(data);

            // an error occured
            if (data.code == 1) {
              $('#blend-box-summary').html(data.msg);
              return;
            }

            // successfully updated row
            $('#blend-box-summary').html(data.msg);

            setTimeout(function() {
              flatUi.blendBox.close();
            }, 400);

            _this.loadPage(_this.currentPage);
          });
        }
      });
    });
  },

  /*
  show a box, used to delete all selected entries
  */
  showDeleteAllBlendBox: function(rowIds) {
    var _this = this;

    flatUi.blendBox.open(rowIds.length > 1 ? rowIds.length + " Eintr&auml;ge l&ouml;schen" : "Eintrag l&ouml;schen", {
      fields: [{
        type: 'submit',
        caption: 'Best&auml;tigen'
      }],
      submit: function(data) {
        var i = 0;

        for (var prop in rowIds) {
          _this.delete(rowIds[prop], function(data) {
            i++;
            if (i < Object.keys(rowIds).length) return;
            flatUi.blendBox.close();
            _this.loadPage(_this.currentPage);
          });
        }
        rowIds = {};
      }
    });
  }
};
