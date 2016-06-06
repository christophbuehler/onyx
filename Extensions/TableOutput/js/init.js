// var TableOutputController = function() {
//   this.serverUrl = '../tableOutput/';
// };
//
// TableOutputController.prototype = {
//   init: function() {
//     var _this = this;
//
//     $('.table-output-bounding-box').each(function() {
//       // new TableOutput(this, _this.serverUrl);
//     });
//   }
// };
//
// var tableOutputController = new TableOutputController();
//
// $(function() {
//   tableOutputController.init();
// });

var utils = {
  parseResponse: function(data) {
    var res;
    try {
      res = JSON.parse(data);
    } catch (e) {
      res = { code: 1, msg: data };
      console.warn(sprintf('Ein Fehler ist aufgetreten: %s', data));
    }

    return res;
  }
}
