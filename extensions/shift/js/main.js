var clickEvt = 'mousedown';

var flatUi = {
  checkBox: {
    init: function() {
      $('body').on(clickEvt, '.checkbox', function() {
        $(this).attr('data-checked', ($(this).attr('data-checked') == 'true') ? 'false' : 'true');
      });
    }
  },
  search: {
    tmpTimeout: null,
    show: function() {
      clearTimeout(this.tmpTimeout);
      $('body').css('overflow', 'hidden');
      $('#search-btn').parent().attr('data-visible', 'true');
      $('#search-overlap').css({
        'display': 'block'
      });
      this.tmpTimeout = setTimeout(function() {
        $('#search-overlap').attr('data-visible', 'true');
      }, 100);
    },
    hide: function() {
      clearTimeout(this.tmpTimeout);
      $('#search-btn').parent().attr('data-visible', 'false');
      $('h1, h2').css('opacity', '1');
      $('#search-overlap').css({
        'opacity': '0'
      });
      this.tmpTimeout = setTimeout(function() {
        $('#search-overlap').css({
          'display': 'none'
        });
        $('body').removeAttr('style');
      }, 200);
    },
    init: function() {
      var _this = this;
      $('.search').on(clickEvt, '#search-btn', function() {
        if ($(this).parent().attr('data-visible') == 'true') {
          alert("search");
          return;
        }
        _this.show();
      });
      $('#search-overlap').on(clickEvt, function() {
        _this.hide();
      });
    }
  },
  initBoolInput: function() {
    $('body').on(clickEvt, '.bool-input-yes', function() {
      $(this).parent().attr('data-value', 'false');
      $(this).parent().find('input').val('0');
    });
    $('body').on(clickEvt, '.bool-input-no', function() {
      $(this).parent().attr('data-value', 'true');
      $(this).parent().find('input').val('1');
    });
  },
  initNumericInput: function() {
    $('body').on(clickEvt, '.numeric-up', function() {
      var input = $(this).parent().find('.numeric-input');
      $(input).val(~~$(input).val() + 1);
    });
    $('body').on(clickEvt, '.numeric-down', function() {
      var input = $(this).parent().find('.numeric-input');
      $(input).val(~~$(input).val() - 1);
    });
  },
  initAutoComplete: function() {
    var autocompleteEl = null,
      selIndex;

    $('body').on('keyup', '[data-link]', function() {
      var _this = this,
        val = $(this).val();

      $(this).attr('data-value', 'NULL');
      if (val === '') {
        autocompleteEl = null;
        $(this).parent().find('.auto-complete-box').fadeOut(200);
        return;
      }
      autocompleteEl = this;
      $(this).parent().find('.auto-complete-box').fadeIn(200);
      $.post($(this).attr('data-link'), {
        l: val
      }, function(data) {
        data = JSON.parse(data);



        if (data.code == 1) {

          alert("an error occured: " + data.msg);

          return;

        }



        $(_this).parent().find('.auto-complete-box > ul').html(data.msg);

        updateSelectedIndex();

      });

    });



    $('body').on('keydown', function(evt) {

      if (!autocompleteEl) return;



      switch (evt.keyCode) {

        case 38: // up

          selIndex--;

          break;

        case 40: // down

          selIndex++;

          break;

        case 9: // tab

          assignAutocompleteVal.bind($(autocompleteEl).next().find('ul li')[selIndex])();

          break;

        case 13: // enter

          assignAutocompleteVal.bind($(autocompleteEl).next().find('ul li')[selIndex])();

          evt.preventDefault();

          evt.stopPropagation();

          break;

        default:

          selIndex = 0;

      }

    });



    $('body').on('blur', '[data-link]', function() {

      $(this).parent().find('.auto-complete-box').fadeOut(200);

      autocompleteEl = null;

    });



    $('body').on('focus', '[data-link]', function() {

      $(this).parent().find('.auto-complete-box').fadeIn(200);

      autocompleteEl = this;

      selIndex = 0;

      updateSelectedIndex();

    });



    $('body').on('mousedown', '.auto-complete-box li', function(evt) {

      evt.preventDefault();

      evt.stopPropagation();



      assignAutocompleteVal.bind(this)();

    });



    function updateSelectedIndex() {

      var children;



      if (!autocompleteEl) return;



      children = $(autocompleteEl).next().find('li');



      if (children.length === 0) return;



      if (selIndex < 0) selIndex = children.length - 1;

      else selIndex = selIndex % children.length;



      children.each(function(index) {

        if (index == selIndex) {

          if ($(this).hasClass('selected')) return;

          $(this).addClass('selected');

          return;

        }

        $(this).removeClass('selected');

      });

    }



    function assignAutocompleteVal() {

      var nearestInput = $(this).closest('.form-element').find('input');



      $(nearestInput).val($(this).html());

      $(nearestInput).attr('data-value', $(this).attr('data-value'));



      $(this).parent().parent().fadeOut(200);

      autocompleteEl = null;

    }

  },

  toData: function(form) {

    var data = this.paramsToData($(form).serialize());

    for (var field in data) {

      if (!$(form).find('[name=' + field + ']').is('[data-value]')) continue;

      data[field] = $(form).find('[name=' + field + ']').attr('data-value');

    }

    return data;

  },

  paramsToData: function(text) {

    var data = {};

    if (text === '') return data;

    text.split('&').map(function(el) {

      data[el.split('=')[0]] = el.split('=')[1];

    });

    return data;

  },

  debug: function(type, msg) {

    alert("flatUi error. type: " + type + " message: " + msg);

  },

  init: function() {
    this.initAutoComplete();

    this.checkBox.init();

    this.search.init();



    this.initNumericInput();

    this.initBoolInput();



    $(document).on(clickEvt, '.btn', function(evt) {

      evt.stopPropagation();

    });

  }

};



$(function() {

  flatUi.init();

});
