var SinglePage = function(root, defaultPage, events) {
  this.root = root;
  this.defaultPage = defaultPage;
  this.events = events;

  this.views = [];
  this.didInit = false;
  this.activeView = null;
};

SinglePage.prototype = {
  init: function() {
    this.didInit = true;
    this.views.forEach(function(view) {
      if (view.init) view.init();
    });

    this.update();
    $(window).on('hashchange', this.update.bind(this));
  },
  attach: function(view) {
    for (var i in this.views) {
      if (this.views[i].url != view.url) continue;
      this.error('View ' + view.url + ' was already attached.');
      return;
    }
    this.views.push(view);
    if (this.didInit) view.init();
  },
  update: function() {
    var url = window.location.hash.substr(1);

    if (this.activeView) {

      // this is already the active page
      if (url == this.activeView.url) {
        this.events.onSamePage();
        return;
      }

      // unload the current page
      if (this.activeView.unload) this.activeView.unload();
    }

    // if the page was not found, load the default page
    for (var i in this.views) {
      if (this.views[i].url != url) continue;
      this.activeView = this.views[i];
      this.navigate();
      return;
    }

    // the page was not found,
    // try to load it anyway.
    this.activeView = { url: url == '' ? this.defaultPage : url };
    this.navigate(true);
  },
  navigate: function(newView) {
    var _this = this;
    this.events.onBeforeNavigate(this.activeView, function() {
      // navigation is permitted

      // the view is already buffered
      if (!newView && _this.activeView.html) {
        _this.events.onNavigate(_this.activeView);
        if (_this.activeView.load) _this.activeView.load();
        return;
      }

      _this.events.onLoad(function() {
        $.get(_this.root + _this.activeView.url, {
          contentOnly: true
        }, function(data) {

          // the new view was successfully loaded
          if (newView) {
            _this.views.push(_this.activeView);
          }

          _this.activeView.html = data;
          _this.events.onNavigate(_this.activeView);

          if (_this.activeView.load) _this.activeView.load();
        });
      });
    });
  },
  error: function(msg) {
    console.warn('SinglePage error: ' + msg);
  }
};
