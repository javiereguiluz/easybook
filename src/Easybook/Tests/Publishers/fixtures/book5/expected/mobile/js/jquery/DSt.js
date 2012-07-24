/* DSt:
a simple, agnostic DOM Storage library.
http://github.com/gamache/DSt
AUTHORSHIP:
copyright 2010 pete gamache gamache!@#$!@#gmail.com
licensed under the MIT License and/or the GPL version 2

SYNOPSIS:
DSt uses the localStorage mechanism to provide per-site, persistent
storage of JSON-encodable data.

USAGE:

DSt.set(key, value); // sets stored value for given key
var value = DSt.get(key); // returns stored value for given key

DSt.store(input_elt); // stores value of a form input element
DSt.recall(input_elt); // recalls stored value of a form input elt

DSt.store_form(form_elt); // runs DSt.store(elt) on each form input
DSt.populate_form(form_elt); // runs DSt.recall(elt) on each form input

Element IDs may always be given in place of the elements themselves.
Values handled by DSt.get/DSt.set can be anything JSON-encodable.

You may use jQuery.DSt or $.DSt instead of DST if you're using
jquery.dst.js.
*/


// Developer's note:
// If the following line is changed, 'make jquery' will not work properly.
var DSt // <-- to change the global namespace, do it here
= (function(){var DSt = { // <-- not here

  version: 0.002005,

  get: function (key) {
    var value = localStorage.getItem(key);
    if (value === undefined || value === null)
      value = 'null';
    else
      value = value.toString();
    return JSON.parse(value);
  },
 
  set: function (key, value) {
    return localStorage.setItem(key, JSON.stringify(value));
  },


  store: function (elt) {
    if (typeof(elt) == 'string') elt = document.getElementById(elt);
    if (!elt || elt.name == '') return this; // bail on nameless/missing elt

    var key = DSt._form_elt_key(elt);

    if (elt.type == 'checkbox') {
      DSt.set(key, elt.checked ? 1 : 0);
    }
    else if (elt.type == 'radio') {
      DSt.set(key, DSt._radio_value(elt));
    }
    else {
      DSt.set(key, elt.value || '');
    }

    return this;
  },

  recall: function (elt) {
    if (typeof(elt) == 'string') elt = document.getElementById(elt);
    if (!elt || elt.name == '') return this; // bail on nameless/missing elt
    
    var key = DSt._form_elt_key(elt);
    var stored_value = DSt.get(key);

    if (elt.type == 'checkbox') {
      elt.checked = !!stored_value;
    }
    else if (elt.type == 'radio') {
      if (elt.value == stored_value) elt.checked = true;
    }
    else {
      elt.value = stored_value || '';
    }

    return this;
  },

  // returns a key string, based on form name and form element name
  _form_elt_key: function (form_elt) {
    return '_form_' + form_elt.form.name + '_field_' + form_elt.name;
  },

  // returns the selected value of a group of radio buttons, or null
  // if none are selected
  _radio_value: function (radio_elt) {
    if (typeof(radio_elt)=='string')
      radio_elt=document.getElementById(radio_elt);

    var radios = radio_elt.form.elements[radio_elt.name];
    var nradios = radios.length;
    var value = null;
    for (var i=0; i<nradios; i++) {
      if (radios[i].checked) value = radios[i].value;
    }
    return value;
  },



  recall_form: function (form) {
    return DSt._apply_fn_to_form_inputs(form, DSt.recall);
  },

  store_form: function (form) {
    return DSt._apply_fn_to_form_inputs(form, DSt.store);
  },

  _apply_fn_to_form_inputs: function (form, fn) {
    if (typeof(form)=='string') form=document.getElementById(form);
    var nelts = form.elements.length;
    for (var i=0; i<nelts; i++) {
      var node = form.elements[i];
      if (node.tagName == 'TEXTAREA' ||
          node.tagName == 'INPUT' &&
             node.type != 'file' &&
             node.type != 'button' &&
             node.type != 'image' &&
             node.type != 'password' &&
             node.type != 'submit' &&
             node.type != 'reset' ) { fn(node); }
    }
    return this;
  },
  


  // _storage_types() returns a string containing every supported
  // storage mechanism
  _storage_types: function () {
    var st = '';
    for (var i in window) {
      if (i=='sessionStorage' || i=='globalStorage' ||
          i=='localStorage' || i=='openDatabase' ) {
        st += st ? (' '+i) : i;
      }
    }
    return st;
  },

  javascript_accepts_trailing_comma: false
};
return DSt;
})();