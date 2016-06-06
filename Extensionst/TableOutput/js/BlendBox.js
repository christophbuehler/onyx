// var BlendBox = function(tableOutput, level) {
//   this.tableOutput = tableOutput;
//   this.container = $('<div class="blend-box"></div>');
//   $('body').append(this.container);
//   this.level = level;
//   this.init();
// };
// BlendBox.prototype = {
//   visible: false,
//   submitFunction: null,
//   abortFunction: null,
//   hasErrors: false,
//   hasSubmit: false,
//   reorder: function(fields) {
//     var newFields = [];
//     fields.map(function(field) {
//       if (!field.required) return;
//       newFields.push(field);
//     });
//     fields.map(function(field) {
//       if (field.required) return;
//       newFields.push(field);
//     });
//     return newFields;
//   },
//   hasRequiredFields: function(fields) {
//     var a = false,
//       b = false;
//     fields.map(function(field) {
//       if (field.required === null) return;
//       if (field.type == 'hidden') return;
//       if (field.required) a = true;
//       else b = true;
//     });
//     return a && b;
//   },
//   open: function(caption, data) {
//     var content,
//       hasReqFields = this.hasRequiredFields(data.fields);
//
//     content = '<form data-show-all="' + (hasReqFields ? 'false' : 'true') + '" class="blend-box-form form-elements-container"><span class="caption">' + caption + '</span>' + (hasReqFields ? '<div class="blend-box-required" class="entypo-eye"></div>' : '') + '<div class="blend-box-close material-icons">close</div><div class="blend-box-content">';
//     this.visible = true;
//     if (!data || !data.submit) this.hasSubmit = false;
//     else this.hasSubmit = true;
//     this.submitFunction = (data && data.submit) ? data.submit.bind(this) : null;
//     this.abortFunction = (data && data.abort) ? data.abort.bind(this) : null;
//     $(this.container).find('.content').html();
//     $(this.container).find('.content').attr('data-single-col', (data && data.fields.length < 5) ? 'true' : 'false');
//     data.fields = this.reorder(data.fields);
//     if (data && data.fields) data.fields.map(function(el) {
//       content += sprintf("%s", el.type == 'hidden' ? '' : sprintf('<label data-required="%s" class="form-element">', (el.required === null || el.required || el.type == 'submit' ? 'true' : 'false')));
//       switch (el.type) {
//         /*
//          * if the name starts with 'image_',
//          * the blob is treated as an image
//          * */
//         case 'BLOB':
//           content += '<span>' + el.caption + '</span>';
//           content += '<input' + (el.link ? ' autocomplete="off" data-link="' + el.link.id + '"' : '');
//           content += ' ' + (el.content ? 'data-value="' + el.value + '" ' : '');
//           content += 'name="' + el.name + '" value="' + (el.content ? el.content : (el.value ? el.value : ""));
//           content += '" class="input blob-input"' + (el.disabled ? ' disabled' : '') + ' type="text">';
//           break;
//         case 'TINY':
//         case 'bool':
//           var val = el.value == 1 ? "true" : "false";
//           content += '<span>' + el.caption + '</span>';
//           content += '<div class="bool-input-container" data-value="';
//           content += val + '"><input type="hidden" name="' + el.name + '" value="' + (val == "true" ? 1 : 0);
//           content += '"><div class="bool-input-yes">ja</div><div class="bool-input-no">nein</div></div>';
//           break;
//         case 'VAR_STRING':
//         case 'text':
//           content += sprintf('<paper-input label="%s"></paper-input>', el.caption);
//
//           /* content += '<span>' + el.caption + '</span>';
//           content += '<input' + (el.link ? ' autocomplete="off" ' + (el.content ? '' : 'data-value="NULL" ') + 'data-link="' + el.link.id + '"' : '');
//           content += ' ' + (el.hasOwnProperty('content') ? 'data-value="' + el.value + '" ' : '');
//           content += 'name="' + el.name + '" value="' + (el.hasOwnProperty('content') ? el.content : (el.value ? el.value : ""));
//           content += '" class="input"' + (el.disabled ? ' disabled' : '') + ' type="text">'; */
//           break;
//         case 'DATE':
//           content += '<span>' + el.caption + '</span>';
//           content += '<input autocomplete="off"' + (el.link ? ' data-link="' + el.link.id + '"' : '');
//           content += ' ' + (el.content ? 'data-value="' + el.value + '" ' : '');
//           content += 'name="' + el.name + '" value="' + (el.content ? el.content : (el.value ? el.value : ""));
//           content += '" class="input date-input" type="text">';
//           break;
//         case 'password':
//           content += '<span>' + el.caption + '</span>';
//           content += '<input' + (el.link ? ' autocomplete="off" data-link="' + el.link.id + '"' : '');
//           content += ' ' + (el.content ? 'data-value="' + el.value + '" ' : '');
//           content += 'name="' + el.name + '" value="' + (el.content ? el.content : (el.value ? el.value : ""));
//           content += '" class="input password-input" type="password">';
//           break;
//         case 'submit':
//           content += '<div class="blend-box-summary"></div><input type="submit" class="submit" value="' + el.caption + '">';
//           break;
//         case 'LONG':
//         case 'number':
//           content += '<span>' + el.caption + '</span>';
//           content += '<div class="numeric-container"><div class="numeric-down">-</div>';
//           content += '<input' + (el.link ? ' autocomplete="off" data-link="' + el.link.id + '"' : '');
//           content += ' ' + (el.content ? 'data-value="' + el.value + '" ' : '') + 'name="' + el.name + '"';
//           content += 'value="' + (el.content || (el.value || "0")) + '" class="input numeric-input" type="text"><div class="numeric-up">+</div></div>';
//           break;
//         case 'hidden':
//           content += '<input type="hidden" name="' + el.name + '" class="hidden-input" value="' + (el.content ? el.content : (el.value ? el.value : "")) + '">';
//           break;
//         default:
//           console.warn("Undefined shift blendBox field type: " + el.type);
//       }
//
//       if (el.link && el.link.reference) content += "<div class='new-link-btn' data-title='" + el.caption + "' data-reference='" + el.link.reference + "'>neu</div>";
//
//       if (el.link) content += '<div class="auto-complete-box"><ul></ul></div>';
//       content += el.type == 'hidden' ? '' : '</label>';
//     });
//     content += '</div></form>';
//     $(this.container).find('.content').html(content);
//     this.initDateInput();
//     this.show();
//     // $(this.container).find('.content').find('input:not(.hidden-input)').eq(0).focus().select();
//   },
//   close: function() {
//     if (!this.visible) return;
//     this.visible = false;
//     if (this.abortFunction === null || this.abortFunction()) this.hide();
//   },
//   show: function() {
//     var _this = this;
//     $('html').css('overflow', 'hidden');
//     $(this.container).css('display', 'block');
//     $(_this.container).find('.blend-box-container').fadeIn(400);
//     $(_this.container).find('.content').addClass('visible');
//   },
//   hide: function() {
//     var _this = this;
//     $('html').removeAttr('style');
//     $(this.container).find('.content').removeClass('visible');
//     $(_this.container).find('.blend-box-container').fadeOut(200, function() {
//       $(_this.container).css('display', 'none');
//     });
//   },
//   invokeSubmit: function() {
//     if (!this.submitFunction) return;
//     this.clearErrors();
//     // $(this.container).find('.content').find('input:not(.hidden-input)').eq(0).focus().select();
//     this.submitFunction(flatUi.toData($(this.container).find('.blend-box-form')));
//   },
//   error: function(msg) {
//     $(this.container).find('.blend-box-summary').fadeOut(200, function() {
//       $(this).html(msg).fadeIn(200);
//     });
//   },
//   success: function(msg) {
//     $(this.container).find('.blend-box-summary').fadeOut(200, function() {
//       $(this).html(msg).fadeIn(200);
//     });
//   },
//   clearErrors: function() {
//     $(this.container).find('.content [class=form-error]').remove();
//     this.hasErrors = false;
//   },
//   debug: function(msg) {
//     flatUi.debug('blendBox', msg);
//   },
//   initDateInput: function() {
//     $('.date-input').datepicker({
//       dateFormat: "dd.mm.yy"
//     });
//     $('.date-input').on('keyup, change', function() {
//       // convert value to mysql database format
//       var parts = $(this).val().split('.');
//       $(this).attr('data-value', parts[2] + '-' + parts[1] + '-' + parts[0]);
//     });
//   },
//   init: function() {
//     var _this = this;
//     this.container.append('<div class="blend-box-container"></div><div class="content"></div>');
//     $(this.container).on('submit', '.blend-box-form', function(evt) {
//       evt.stopPropagation();
//       evt.preventDefault();
//       this.invokeSubmit();
//     }.bind(this));
//     // close blend box
//     $(this.container).on(clickEvt, '.blend-box-close', function(evt) {
//       this.close();
//     }.bind(this));
//     $(this.container).on(clickEvt, '.new-link-btn', function() {
//       var title = $(this).attr('data-title'),
//           input = $(this).parent().find('input'),
//           box = new BlendBox(_this.tableOutput, _this.level + 1);
//
//       $.post($(this).attr('data-reference') + '/new_entry', {
//         tableOutputId: box.level
//       }, function(data) {
//
//         data = JSON.parse(data);
//         data.push({
//           type: 'submit',
//           caption: 'Speichern'
//         });
//
//         box.open(title, {
//           fields: data,
//           submit: function(data) {
//             _this.tableOutput.insert(box.level, data, function(data) {
//               data = JSON.parse(data);
//               // successfully inserted row
//               if (data.code === 0) {
//                 box.success(data.msg);
//                 input.attr('data-value', data.id);
//
//                 $.post($(input).attr('data-link'), {
//                   reverse: true,
//                   id: data.id
//                 }, function(data) {
//                   data = JSON.parse(data);
//                   input.val(data.text);
//                   box.close();
//                 });
//
//                 return;
//               }
//               // an error occured
//               box.error(data.msg);
//             });
//           }
//         });
//       });
//     });
//     $(this.container).on(clickEvt, '.blend-box-required', function(evt) {
//       if ($('.blend-box-form').attr('data-show-all') == 'true') {
//         $('.blend-box-form').attr('data-show-all', 'false');
//         return;
//       }
//       $(this.container).find('.blend-box-form').attr('data-show-all', 'true');
//     }.bind(this));
//   }
// };
