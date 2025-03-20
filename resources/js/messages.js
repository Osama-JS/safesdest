/*!
 *  Lang.js for Laravel localization in JavaScript.
 *
 *  @version 1.1.10
 *  @license MIT https://github.com/rmariuzzo/Lang.js/blob/master/LICENSE
 *  @site    https://github.com/rmariuzzo/Lang.js
 *  @author  Rubens Mariuzzo <rubens@mariuzzo.com>
 */
(function (root, factory) {
  'use strict';
  if (typeof define === 'function' && define.amd) {
    define([], factory);
  } else if (typeof exports === 'object') {
    module.exports = factory();
  } else {
    root.Lang = factory();
  }
})(this, function () {
  'use strict';
  function inferLocale() {
    if (typeof document !== 'undefined' && document.documentElement) {
      return document.documentElement.lang;
    }
  }
  function convertNumber(str) {
    if (str === '-Inf') {
      return -Infinity;
    } else if (str === '+Inf' || str === 'Inf' || str === '*') {
      return Infinity;
    }
    return parseInt(str, 10);
  }
  var intervalRegexp =
    /^({\s*(\-?\d+(\.\d+)?[\s*,\s*\-?\d+(\.\d+)?]*)\s*})|([\[\]])\s*(-Inf|\*|\-?\d+(\.\d+)?)\s*,\s*(\+?Inf|\*|\-?\d+(\.\d+)?)\s*([\[\]])$/;
  var anyIntervalRegexp =
    /({\s*(\-?\d+(\.\d+)?[\s*,\s*\-?\d+(\.\d+)?]*)\s*})|([\[\]])\s*(-Inf|\*|\-?\d+(\.\d+)?)\s*,\s*(\+?Inf|\*|\-?\d+(\.\d+)?)\s*([\[\]])/;
  var defaults = { locale: 'en' };
  var Lang = function (options) {
    options = options || {};
    this.locale = options.locale || inferLocale() || defaults.locale;
    this.fallback = options.fallback;
    this.messages = options.messages;
  };
  Lang.prototype.setMessages = function (messages) {
    this.messages = messages;
  };
  Lang.prototype.getLocale = function () {
    return this.locale || this.fallback;
  };
  Lang.prototype.setLocale = function (locale) {
    this.locale = locale;
  };
  Lang.prototype.getFallback = function () {
    return this.fallback;
  };
  Lang.prototype.setFallback = function (fallback) {
    this.fallback = fallback;
  };
  Lang.prototype.has = function (key, locale) {
    if (typeof key !== 'string' || !this.messages) {
      return false;
    }
    return this._getMessage(key, locale) !== null;
  };
  Lang.prototype.get = function (key, replacements, locale) {
    if (!this.has(key, locale)) {
      return key;
    }
    var message = this._getMessage(key, locale);
    if (message === null) {
      return key;
    }
    if (replacements) {
      message = this._applyReplacements(message, replacements);
    }
    return message;
  };
  Lang.prototype.trans = function (key, replacements) {
    return this.get(key, replacements);
  };
  Lang.prototype.choice = function (key, number, replacements, locale) {
    replacements = typeof replacements !== 'undefined' ? replacements : {};
    replacements.count = number;
    var message = this.get(key, replacements, locale);
    if (message === null || message === undefined) {
      return message;
    }
    var messageParts = message.split('|');
    var explicitRules = [];
    for (var i = 0; i < messageParts.length; i++) {
      messageParts[i] = messageParts[i].trim();
      if (anyIntervalRegexp.test(messageParts[i])) {
        var messageSpaceSplit = messageParts[i].split(/\s/);
        explicitRules.push(messageSpaceSplit.shift());
        messageParts[i] = messageSpaceSplit.join(' ');
      }
    }
    if (messageParts.length === 1) {
      return message;
    }
    for (var j = 0; j < explicitRules.length; j++) {
      if (this._testInterval(number, explicitRules[j])) {
        return messageParts[j];
      }
    }
    var pluralForm = this._getPluralForm(number);
    return messageParts[pluralForm];
  };
  Lang.prototype.transChoice = function (key, count, replacements) {
    return this.choice(key, count, replacements);
  };
  Lang.prototype._parseKey = function (key, locale) {
    if (typeof key !== 'string' || typeof locale !== 'string') {
      return null;
    }
    var segments = key.split('.');
    var source = segments[0].replace(/\//g, '.');
    return {
      source: locale + '.' + source,
      sourceFallback: this.getFallback() + '.' + source,
      entries: segments.slice(1)
    };
  };
  Lang.prototype._getMessage = function (key, locale) {
    locale = locale || this.getLocale();
    key = this._parseKey(key, locale);
    if (this.messages[key.source] === undefined && this.messages[key.sourceFallback] === undefined) {
      return null;
    }
    var message = this.messages[key.source];
    var entries = key.entries.slice();
    var subKey = '';
    while (entries.length && message !== undefined) {
      var subKey = !subKey ? entries.shift() : subKey.concat('.', entries.shift());
      if (message[subKey] !== undefined) {
        message = message[subKey];
        subKey = '';
      }
    }
    if (typeof message !== 'string' && this.messages[key.sourceFallback]) {
      message = this.messages[key.sourceFallback];
      entries = key.entries.slice();
      subKey = '';
      while (entries.length && message !== undefined) {
        var subKey = !subKey ? entries.shift() : subKey.concat('.', entries.shift());
        if (message[subKey]) {
          message = message[subKey];
          subKey = '';
        }
      }
    }
    if (typeof message !== 'string') {
      return null;
    }
    return message;
  };
  Lang.prototype._findMessageInTree = function (pathSegments, tree) {
    while (pathSegments.length && tree !== undefined) {
      var dottedKey = pathSegments.join('.');
      if (tree[dottedKey]) {
        tree = tree[dottedKey];
        break;
      }
      tree = tree[pathSegments.shift()];
    }
    return tree;
  };
  Lang.prototype._applyReplacements = function (message, replacements) {
    for (var replace in replacements) {
      message = message.replace(new RegExp(':' + replace, 'gi'), function (match) {
        var value = replacements[replace];
        var allCaps = match === match.toUpperCase();
        if (allCaps) {
          return value.toUpperCase();
        }
        var firstCap =
          match ===
          match.replace(/\w/i, function (letter) {
            return letter.toUpperCase();
          });
        if (firstCap) {
          return value.charAt(0).toUpperCase() + value.slice(1);
        }
        return value;
      });
    }
    return message;
  };
  Lang.prototype._testInterval = function (count, interval) {
    if (typeof interval !== 'string') {
      throw 'Invalid interval: should be a string.';
    }
    interval = interval.trim();
    var matches = interval.match(intervalRegexp);
    if (!matches) {
      throw 'Invalid interval: ' + interval;
    }
    if (matches[2]) {
      var items = matches[2].split(',');
      for (var i = 0; i < items.length; i++) {
        if (parseInt(items[i], 10) === count) {
          return true;
        }
      }
    } else {
      matches = matches.filter(function (match) {
        return !!match;
      });
      var leftDelimiter = matches[1];
      var leftNumber = convertNumber(matches[2]);
      if (leftNumber === Infinity) {
        leftNumber = -Infinity;
      }
      var rightNumber = convertNumber(matches[3]);
      var rightDelimiter = matches[4];
      return (
        (leftDelimiter === '[' ? count >= leftNumber : count > leftNumber) &&
        (rightDelimiter === ']' ? count <= rightNumber : count < rightNumber)
      );
    }
    return false;
  };
  Lang.prototype._getPluralForm = function (count) {
    switch (this.locale) {
      case 'az':
      case 'bo':
      case 'dz':
      case 'id':
      case 'ja':
      case 'jv':
      case 'ka':
      case 'km':
      case 'kn':
      case 'ko':
      case 'ms':
      case 'th':
      case 'tr':
      case 'vi':
      case 'zh':
        return 0;
      case 'af':
      case 'bn':
      case 'bg':
      case 'ca':
      case 'da':
      case 'de':
      case 'el':
      case 'en':
      case 'eo':
      case 'es':
      case 'et':
      case 'eu':
      case 'fa':
      case 'fi':
      case 'fo':
      case 'fur':
      case 'fy':
      case 'gl':
      case 'gu':
      case 'ha':
      case 'he':
      case 'hu':
      case 'is':
      case 'it':
      case 'ku':
      case 'lb':
      case 'ml':
      case 'mn':
      case 'mr':
      case 'nah':
      case 'nb':
      case 'ne':
      case 'nl':
      case 'nn':
      case 'no':
      case 'om':
      case 'or':
      case 'pa':
      case 'pap':
      case 'ps':
      case 'pt':
      case 'so':
      case 'sq':
      case 'sv':
      case 'sw':
      case 'ta':
      case 'te':
      case 'tk':
      case 'ur':
      case 'zu':
        return count == 1 ? 0 : 1;
      case 'am':
      case 'bh':
      case 'fil':
      case 'fr':
      case 'gun':
      case 'hi':
      case 'hy':
      case 'ln':
      case 'mg':
      case 'nso':
      case 'xbr':
      case 'ti':
      case 'wa':
        return count === 0 || count === 1 ? 0 : 1;
      case 'be':
      case 'bs':
      case 'hr':
      case 'ru':
      case 'sr':
      case 'uk':
        return count % 10 == 1 && count % 100 != 11
          ? 0
          : count % 10 >= 2 && count % 10 <= 4 && (count % 100 < 10 || count % 100 >= 20)
            ? 1
            : 2;
      case 'cs':
      case 'sk':
        return count == 1 ? 0 : count >= 2 && count <= 4 ? 1 : 2;
      case 'ga':
        return count == 1 ? 0 : count == 2 ? 1 : 2;
      case 'lt':
        return count % 10 == 1 && count % 100 != 11
          ? 0
          : count % 10 >= 2 && (count % 100 < 10 || count % 100 >= 20)
            ? 1
            : 2;
      case 'sl':
        return count % 100 == 1 ? 0 : count % 100 == 2 ? 1 : count % 100 == 3 || count % 100 == 4 ? 2 : 3;
      case 'mk':
        return count % 10 == 1 ? 0 : 1;
      case 'mt':
        return count == 1
          ? 0
          : count === 0 || (count % 100 > 1 && count % 100 < 11)
            ? 1
            : count % 100 > 10 && count % 100 < 20
              ? 2
              : 3;
      case 'lv':
        return count === 0 ? 0 : count % 10 == 1 && count % 100 != 11 ? 1 : 2;
      case 'pl':
        return count == 1 ? 0 : count % 10 >= 2 && count % 10 <= 4 && (count % 100 < 12 || count % 100 > 14) ? 1 : 2;
      case 'cy':
        return count == 1 ? 0 : count == 2 ? 1 : count == 8 || count == 11 ? 2 : 3;
      case 'ro':
        return count == 1 ? 0 : count === 0 || (count % 100 > 0 && count % 100 < 20) ? 1 : 2;
      case 'ar':
        return count === 0
          ? 0
          : count == 1
            ? 1
            : count == 2
              ? 2
              : count % 100 >= 3 && count % 100 <= 10
                ? 3
                : count % 100 >= 11 && count % 100 <= 99
                  ? 4
                  : 5;
      default:
        return 0;
    }
  };
  return Lang;
});

(function () {
  Lang = new Lang();
  Lang.setMessages({
    'ar.strings': {
      Academy: '\u0627\u0644\u0623\u0643\u0627\u062f\u064a\u0645\u064a\u0629',
      Accordion: '\u0627\u0644\u0623\u0643\u0648\u0631\u062f\u064a\u0648\u0646',
      Account: '\u0627\u0644\u062d\u0633\u0627\u0628',
      'Account Settings': '\u0625\u0639\u062f\u0627\u062f\u0627\u062a \u0627\u0644\u062d\u0633\u0627\u0628',
      Actions: '\u0627\u0644\u0625\u062c\u0631\u0627\u0621\u0627\u062a',
      'Active Users':
        '\u0627\u0644\u0645\u0633\u062a\u062e\u062f\u0645\u064a\u0646 \u0627\u0644\u0646\u0634\u0637\u064a\u0646',
      Add: '\u0625\u0636\u0627\u0641\u0629',
      'Add New Role': '\u0623\u0636\u0641 \u062f\u0648\u0631\u064b\u0627 \u062c\u062f\u064a\u062f\u064b\u0627',
      'Add New User': '\u0625\u0636\u0627\u0641\u0629 \u0645\u0633\u062a\u062e\u062f\u0645 \u062c\u062f\u064a\u062f',
      'Add Product': '\u0625\u0636\u0627\u0641\u0629 \u0645\u0646\u062a\u062c',
      'Add new roles with customized permissions as per your requirement':
        '\u0623\u0636\u0641 \u0623\u062f\u0648\u0627\u0631\u064b\u0627 \u062c\u062f\u064a\u062f\u0629 \u0645\u0639 \u0635\u0644\u0627\u062d\u064a\u0627\u062a \u0645\u062e\u0635\u0635\u0629 \u062d\u0633\u0628 \u0645\u062a\u0637\u0644\u0628\u0627\u062a\u0643',
      'Address & Billing':
        '\u0627\u0644\u0639\u0646\u0648\u0627\u0646 \u0648\u0627\u0644\u0641\u0648\u0627\u062a\u064a\u0631',
      Administrator: '\u0645\u062f\u064a\u0631',
      Advance: '\u0645\u062a\u0642\u062f\u0645',
      Advanced: '\u0645\u062a\u0642\u062f\u0645',
      Alerts: '\u0627\u0644\u062a\u0646\u0628\u064a\u0647\u0627\u062a',
      'All Customer': '\u062c\u0645\u064a\u0639 \u0627\u0644\u0639\u0645\u0644\u0627\u0621',
      'All Customers': '\u062c\u0645\u064a\u0639 \u0627\u0644\u0639\u0645\u0644\u0627\u0621',
      Analytics: '\u062a\u062d\u0644\u064a\u0644\u0627\u062a',
      'Apex Charts': '\u0645\u062e\u0637\u0637\u0627\u062a \u0623\u0628\u064a\u0643\u0633',
      'App Brand': '\u0639\u0644\u0627\u0645\u0629 \u0627\u0644\u062a\u0637\u0628\u064a\u0642',
      Apps: '\u062a\u0637\u0628\u064a\u0642\u0627\u062a',
      'Apps & Pages':
        '\u0627\u0644\u062a\u0637\u0628\u064a\u0642\u0627\u062a \u0648\u0627\u0644\u0635\u0641\u062d\u0627\u062a',
      Article: '\u0627\u0644\u0645\u0642\u0627\u0644',
      Authentications: '\u0627\u0644\u0645\u0635\u0627\u062f\u0642\u0627\u062a',
      Avatar: '\u0627\u0644\u0635\u0648\u0631\u0629 \u0627\u0644\u0631\u0645\u0632\u064a\u0629',
      Badges: '\u0627\u0644\u0634\u0627\u0631\u0627\u062a',
      Basic: '\u0623\u0633\u0627\u0633\u064a',
      'Basic Inputs': '\u0645\u062f\u062e\u0644\u0627\u062a \u0623\u0633\u0627\u0633\u064a\u0629',
      'Billing & Plans': '\u0627\u0644\u0641\u0648\u062a\u0631\u0629 \u0648\u0627\u0644\u062e\u0637\u0637',
      Blank: '\u0641\u0627\u0631\u063a',
      BlockUI: '\u0645\u0646\u0639 \u0648\u0627\u062c\u0647\u0629 \u0627\u0644\u0645\u0633\u062a\u062e\u062f\u0645',
      Buttons: '\u0627\u0644\u0623\u0632\u0631\u0627\u0631',
      CRM: '\u0625\u062f\u0627\u0631\u0629 \u0639\u0644\u0627\u0642\u0627\u062a \u0627\u0644\u0639\u0645\u0644\u0627\u0621',
      Calendar: '\u0627\u0644\u062a\u0642\u0648\u064a\u0645',
      Cards: '\u0627\u0644\u0628\u0637\u0627\u0642\u0627\u062a',
      Carousel: '\u0634\u0631\u064a\u0637 \u0627\u0644\u062a\u0645\u0631\u064a\u0631',
      Categories: '\u0627\u0644\u0641\u0626\u0627\u062a',
      Category: '\u0627\u0644\u0641\u0626\u0629',
      'Category List': '\u0642\u0627\u0626\u0645\u0629 \u0627\u0644\u0641\u0626\u0627\u062a',
      ChartJS:
        '\u0627\u0644\u0631\u0633\u0645 \u0627\u0644\u0628\u064a\u0627\u0646\u064a \u0634\u0628\u064a\u0628\u0629',
      Charts: '\u0627\u0644\u0631\u0633\u0648\u0645 \u0627\u0644\u0628\u064a\u0627\u0646\u064a\u0629',
      'Charts & Maps':
        '\u0627\u0644\u0631\u0633\u0648\u0645 \u0627\u0644\u0628\u064a\u0627\u0646\u064a\u0629 \u0648\u0627\u0644\u062e\u0631\u0627\u0626\u0637',
      Chat: '\u0627\u0644\u062f\u0631\u062f\u0634\u0629',
      Checkout: '\u0627\u0644\u0633\u062f\u0627\u062f',
      Close: '\u0625\u063a\u0644\u0627\u0642',
      Collapse: '\u0627\u0646\u0637\u0648\u0627\u0621',
      'Collapsed menu': '\u0627\u0644\u0642\u0627\u0626\u0645\u0629 \u0627\u0644\u0645\u0637\u0648\u064a\u0629',
      'Coming Soon': '\u0642\u0631\u064a\u0628\u064b\u0627',
      Components: '\u0627\u0644\u0645\u0643\u0648\u0646\u0627\u062a',
      'Confirm Password':
        '\u062a\u0623\u0643\u064a\u062f \u0643\u0644\u0645\u0629 \u0627\u0644\u0645\u0631\u0648\u0631',
      Connections: '\u0627\u0644\u0627\u062a\u0635\u0627\u0644\u0627\u062a',
      Container: '\u062d\u0627\u0648\u064a\u0629',
      'Content nav + Sidebar':
        '\u0634\u0631\u064a\u0637 \u0627\u0644\u062a\u0646\u0642\u0644 \u0644\u0644\u0645\u062d\u062a\u0648\u0649 + \u0627\u0644\u0634\u0631\u064a\u0637 \u0627\u0644\u062c\u0627\u0646\u0628\u064a',
      'Content navbar':
        '\u0634\u0631\u064a\u0637 \u0627\u0644\u062a\u0646\u0642\u0644 \u0644\u0644\u0645\u062d\u062a\u0648\u0649',
      'Course Details': '\u062a\u0641\u0627\u0635\u064a\u0644 \u0627\u0644\u062f\u0648\u0631\u0629',
      Cover: '\u0627\u0644\u063a\u0644\u0627\u0641',
      'Create Deal': '\u0625\u0646\u0634\u0627\u0621 \u0635\u0641\u0642\u0629',
      'Created At': '\u062a\u0627\u0631\u064a\u062e \u0627\u0644\u0625\u0646\u0634\u0627\u0621',
      'Custom Options': '\u062e\u064a\u0627\u0631\u0627\u062a \u0645\u062e\u0635\u0635\u0629',
      Customer: '\u0639\u0645\u064a\u0644',
      'Customer Details': '\u062a\u0641\u0627\u0635\u064a\u0644 \u0627\u0644\u0639\u0645\u064a\u0644',
      Dashboard: '\u0644\u0648\u062d\u0629 \u0627\u0644\u0642\u064a\u0627\u062f\u0629',
      Dashboards: '\u0644\u0648\u062d\u0627\u062a \u0627\u0644\u0642\u064a\u0627\u062f\u0629',
      Datatables: '\u062c\u062f\u0627\u0648\u0644 \u0627\u0644\u0628\u064a\u0627\u0646\u0627\u062a',
      Documentation: '\u0627\u0644\u0648\u062b\u0627\u0626\u0642',
      'Donut drag\u00e9e jelly pie halvah. Danish gingerbread bonbon cookie wafer candy oat cake ice cream. Gummies halvah tootsie roll muffin biscuit icing dessert gingerbread. Pastry ice cream cheesecake fruitcake.':
        '\u062f\u0648\u0646\u0627\u062a \u062f\u0631\u0627\u062c\u064a \u062c\u064a\u0644\u064a \u0641\u0637\u064a\u0631\u0629 \u062d\u0644\u0627\u0648\u0629. \u062e\u0628\u0632 \u0627\u0644\u0632\u0646\u062c\u0628\u064a\u0644 \u0627\u0644\u062f\u0646\u0645\u0627\u0631\u0643\u064a \u0628\u0648\u0646\u0628\u0648\u0646 \u0643\u0648\u0643\u064a \u0648\u064a\u0641\u0631 \u0643\u0627\u0646\u062f\u064a \u0643\u0639\u0643\u0629 \u0627\u0644\u0634\u0648\u0641\u0627\u0646 \u0622\u064a\u0633 \u0643\u0631\u064a\u0645. \u062d\u0644\u0627\u0648\u0629 \u063a\u0648\u0645\u064a \u062a\u0648\u062a\u0633\u064a \u0631\u0648\u0644 \u0645\u0627\u0641\u0646 \u0628\u0633\u0643\u0648\u064a\u062a \u0622\u064a\u0633\u064a\u0646\u063a \u062d\u0644\u0648\u0649 \u062e\u0628\u0632 \u0627\u0644\u0632\u0646\u062c\u0628\u064a\u0644. \u0645\u0639\u062c\u0646\u0627\u062a \u0622\u064a\u0633 \u0643\u0631\u064a\u0645 \u062a\u0634\u064a\u0632 \u0643\u064a\u0643 \u0641\u0627\u0643\u0647\u0629.',
      'Drag & Drop': '\u0627\u0633\u062d\u0628 \u0648\u0623\u0641\u0644\u062a',
      Driver: '\u0633\u0627\u0626\u0642',
      Dropdowns: '\u0627\u0644\u0642\u0648\u0627\u0626\u0645 \u0627\u0644\u0645\u0646\u0633\u062f\u0644\u0629',
      Edit: '\u062a\u0639\u062f\u064a\u0644',
      Editors: '\u0627\u0644\u0645\u062d\u0631\u0631\u064a\u0646',
      Email: '\u0627\u0644\u0628\u0631\u064a\u062f \u0627\u0644\u0625\u0644\u0643\u062a\u0631\u0648\u0646\u064a',
      'Enter phone number': '\u0623\u062f\u062e\u0644 \u0631\u0642\u0645 \u0627\u0644\u0647\u0627\u062a\u0641',
      Error: '\u062e\u0637\u0623',
      'Extended UI':
        '\u0648\u0627\u062c\u0647\u0629 \u0627\u0644\u0645\u0633\u062a\u062e\u062f\u0645 \u0627\u0644\u0645\u0645\u062a\u062f\u0629',
      Extensions: '\u0627\u0644\u0627\u0645\u062a\u062f\u0627\u062f\u0627\u062a',
      Extras: '\u0625\u0636\u0627\u0641\u0627\u062a',
      FAQ: '\u0627\u0644\u0623\u0633\u0626\u0644\u0629 \u0627\u0644\u0634\u0627\u0626\u0639\u0629',
      'File Upload': '\u062a\u062d\u0645\u064a\u0644 \u0627\u0644\u0645\u0644\u0641',
      Fleet: '\u0627\u0644\u0623\u0633\u0637\u0648\u0644',
      Fluid: '\u0645\u0631\u0648\u0646\u0629',
      Fontawesome: '\u0627\u0644\u062e\u0637\u0648\u0637 \u0627\u0644\u0631\u0627\u0626\u0639\u0629',
      Footer: '\u0627\u0644\u062a\u0630\u064a\u064a\u0644',
      'Forgot Password': '\u0646\u0633\u064a\u062a \u0643\u0644\u0645\u0629 \u0627\u0644\u0645\u0631\u0648\u0631',
      'Form Elements': '\u0639\u0646\u0627\u0635\u0631 \u0627\u0644\u0646\u0645\u0648\u0630\u062c',
      'Form Layouts': '\u062a\u062e\u0637\u064a\u0637\u0627\u062a \u0627\u0644\u0646\u0645\u0648\u0630\u062c',
      'Form Validation': '\u0627\u0644\u062a\u062d\u0642\u0642 \u0645\u0646 \u0627\u0644\u0646\u0645\u0648\u0630\u062c',
      'Form Wizard': '\u0645\u0639\u0627\u0644\u062c \u0627\u0644\u0646\u0645\u0648\u0630\u062c',
      Forms: '\u0646\u0645\u0627\u0630\u062c',
      'Forms & Tables': '\u0627\u0644\u0646\u0645\u0627\u0630\u062c \u0648\u0627\u0644\u062c\u062f\u0627\u0648\u0644',
      'Front Pages': '\u0627\u0644\u0635\u0641\u062d\u0627\u062a \u0627\u0644\u0623\u0645\u0627\u0645\u064a\u0629',
      'Full Name': '\u0627\u0644\u0627\u0633\u0645 \u0627\u0644\u0643\u0627\u0645\u0644',
      Fullscreen: '\u0634\u0627\u0634\u0629 \u0643\u0627\u0645\u0644\u0629',
      General: '\u0639\u0627\u0645',
      'Help Center': '\u0645\u0631\u0643\u0632 \u0627\u0644\u0645\u0633\u0627\u0639\u062f\u0629',
      Home: '\u0627\u0644\u0631\u0626\u064a\u0633\u064a\u0629',
      Horizontal: '\u0623\u0641\u0642\u064a',
      'Horizontal Form': '\u0646\u0645\u0648\u0630\u062c \u0623\u0641\u0642\u064a',
      Icons: '\u0627\u0644\u0631\u0645\u0648\u0632',
      Id: '\u0645\u0639\u0631\u0641',
      'Inactive Users':
        '\u0627\u0644\u0645\u0633\u062a\u062e\u062f\u0645\u064a\u0646 \u063a\u064a\u0631 \u0627\u0644\u0646\u0634\u0637\u064a\u0646',
      'Input groups': '\u0645\u062c\u0645\u0648\u0639\u0627\u062a \u0627\u0644\u0625\u062f\u062e\u0627\u0644',
      Invoice: '\u0627\u0644\u0641\u0627\u062a\u0648\u0631\u0629',
      'Jelly-o jelly beans icing pastry cake cake lemon drops. Muffin muffin pie tiramisu halvah cotton candy liquorice caramels.':
        '\u062c\u064a\u0644\u064a-\u0623\u0648 \u062c\u064a\u0644\u064a \u0628\u064a\u0646\u0632 \u0622\u064a\u0633\u064a\u0646\u063a \u0645\u0639\u062c\u0646\u0627\u062a \u0643\u0639\u0643\u0629 \u0643\u0639\u0643\u0629 \u0642\u0637\u0631\u0627\u062a \u0627\u0644\u0644\u064a\u0645\u0648\u0646. \u0645\u0627\u0641\u0646 \u0645\u0627\u0641\u0646 \u0641\u0637\u064a\u0631\u0629 \u062a\u064a\u0631\u0627\u0645\u064a\u0633\u0648 \u062d\u0644\u0627\u0648\u0629 \u0642\u0637\u0646\u064a\u0629 \u062d\u0644\u0648\u0649 \u0639\u0631\u0642 \u0627\u0644\u0633\u0648\u0633 \u0643\u0631\u0627\u0645\u064a\u0644.',
      Kanban: '\u0643\u0627\u0646\u0628\u0627\u0646',
      Landing: '\u0627\u0644\u0647\u0628\u0648\u0637',
      'Laravel Example': '\u0645\u062b\u0627\u0644 \u0644\u0627\u0631\u0627\u0641\u064a\u0644',
      Layouts: '\u0627\u0644\u062a\u062e\u0637\u064a\u0637\u0627\u062a',
      'Leaflet Maps': '\u062e\u0631\u0627\u0626\u0637 \u0627\u0644\u0646\u0634\u0631\u0629',
      List: '\u0627\u0644\u0642\u0627\u0626\u0645\u0629',
      'List Groups': '\u0645\u062c\u0645\u0648\u0639\u0627\u062a \u0627\u0644\u0642\u0648\u0627\u0626\u0645',
      Locations: '\u0627\u0644\u0645\u0648\u0627\u0642\u0639',
      Login: '\u062a\u0633\u062c\u064a\u0644 \u0627\u0644\u062f\u062e\u0648\u0644',
      Logistics: '\u0627\u0644\u0644\u0648\u062c\u0633\u062a\u064a\u0629',
      'Manage Reviews': '\u0625\u062f\u0627\u0631\u0629 \u0627\u0644\u062a\u0642\u064a\u064a\u0645\u0627\u062a',
      'Media Player': '\u0645\u0634\u063a\u0644 \u0627\u0644\u0648\u0633\u0627\u0626\u0637',
      Menu: '\u0627\u0644\u0642\u0627\u0626\u0645\u0629',
      Messages: '\u0627\u0644\u0631\u0633\u0627\u0626\u0644',
      Misc: '\u0645\u062a\u0646\u0648\u0639',
      Miscellaneous: '\u0645\u062a\u0646\u0648\u0639',
      'Modal Examples': '\u0623\u0645\u062b\u0644\u0629 \u0639\u0644\u0649 \u0627\u0644\u0646\u0645\u0627\u0630\u062c',
      Modals: '\u0627\u0644\u0646\u0645\u0627\u0630\u062c',
      'Multi-steps': '\u062e\u0637\u0648\u0627\u062a \u0645\u062a\u0639\u062f\u062f\u0629',
      'My Course': '\u062f\u0648\u0631\u062a\u064a',
      Navbar: '\u0634\u0631\u064a\u0637 \u0627\u0644\u062a\u0646\u0642\u0644',
      'Not Authorized': '\u063a\u064a\u0631 \u0645\u0635\u0631\u062d',
      Notifications: '\u0627\u0644\u0625\u0634\u0639\u0627\u0631\u0627\u062a',
      Numbered: '\u0645\u064f\u0631\u0642\u0651\u0645',
      Offcanvas: '\u0627\u0644\u0642\u0627\u0626\u0645\u0629 \u0627\u0644\u062c\u0627\u0646\u0628\u064a\u0629',
      Order: '\u0627\u0644\u0637\u0644\u0628',
      'Order Details': '\u062a\u0641\u0627\u0635\u064a\u0644 \u0627\u0644\u0637\u0644\u0628',
      'Order List': '\u0642\u0627\u0626\u0645\u0629 \u0627\u0644\u0637\u0644\u0628\u0627\u062a',
      Overview: '\u0646\u0638\u0631\u0629 \u0639\u0627\u0645\u0629',
      Pages: '\u0627\u0644\u0635\u0641\u062d\u0627\u062a',
      'Pagination & Breadcrumbs':
        '\u0627\u0644\u062a\u0631\u0642\u064a\u0645 \u0648\u0641\u062a\u0627\u062a \u0627\u0644\u062e\u0628\u0632',
      Password: '\u0643\u0644\u0645\u0629 \u0627\u0644\u0645\u0631\u0648\u0631',
      Payment: '\u0627\u0644\u062f\u0641\u0639',
      Payments: '\u0627\u0644\u0645\u062f\u0641\u0648\u0639\u0627\u062a',
      'Pending Users':
        '\u0627\u0644\u0645\u0633\u062a\u062e\u062f\u0645\u064a\u0646 \u0627\u0644\u0645\u0639\u0644\u0642\u064a\u0646',
      'Perfect Scrollbar':
        '\u0634\u0631\u064a\u0637 \u0627\u0644\u062a\u0645\u0631\u064a\u0631 \u0627\u0644\u0645\u062b\u0627\u0644\u064a',
      Permission: '\u0627\u0644\u0625\u0630\u0646',
      Phone: '\u0627\u0644\u0647\u0627\u062a\u0641',
      Pickers: '\u0627\u0644\u0645\u0646\u062a\u0642\u064a\u0646',
      Preview: '\u0645\u0639\u0627\u064a\u0646\u0629',
      Pricing: '\u0627\u0644\u062a\u0633\u0639\u064a\u0631',
      'Product List': '\u0642\u0627\u0626\u0645\u0629 \u0627\u0644\u0645\u0646\u062a\u062c\u0627\u062a',
      Products: '\u0627\u0644\u0645\u0646\u062a\u062c\u0627\u062a',
      Profile: '\u0627\u0644\u0645\u0644\u0641 \u0627\u0644\u0634\u062e\u0635\u064a',
      Progress: '\u0627\u0644\u062a\u0642\u062f\u0645',
      Projects: '\u0627\u0644\u0645\u0634\u0627\u0631\u064a\u0639',
      'Property Listing': '\u0642\u0627\u0626\u0645\u0629 \u0627\u0644\u0639\u0642\u0627\u0631\u0627\u062a',
      Referrals: '\u0627\u0644\u0625\u062d\u0627\u0644\u0627\u062a',
      Register: '\u0627\u0644\u062a\u0633\u062c\u064a\u0644',
      'Reset Password':
        '\u0625\u0639\u0627\u062f\u0629 \u062a\u0639\u064a\u064a\u0646 \u0643\u0644\u0645\u0629 \u0627\u0644\u0645\u0631\u0648\u0631',
      Role: '\u0627\u0644\u062f\u0648\u0631',
      Roles: '\u0627\u0644\u0623\u062f\u0648\u0627\u0631',
      'Roles & Permissions':
        '\u0627\u0644\u0623\u062f\u0648\u0627\u0631 \u0648\u0627\u0644\u0635\u0644\u0627\u062d\u064a\u0627\u062a',
      Security: '\u0627\u0644\u0623\u0645\u0627\u0646',
      'Select & Tags': '\u0627\u062e\u062a\u064a\u0627\u0631 \u0648\u0639\u0644\u0627\u0645\u0627\u062a',
      Settings: '\u0627\u0644\u0625\u0639\u062f\u0627\u062f\u0627\u062a',
      'Shipping & Delivery': '\u0627\u0644\u0634\u062d\u0646 \u0648\u0627\u0644\u062a\u0648\u0635\u064a\u0644',
      Sliders: '\u0627\u0644\u0645\u0646\u0632\u0644\u0642\u0627\u062a',
      Spinners: '\u0627\u0644\u062f\u0648\u0627\u0626\u0631',
      'Star Ratings': '\u062a\u0642\u064a\u064a\u0645 \u0627\u0644\u0646\u062c\u0648\u0645',
      Statistics: '\u0627\u0644\u0625\u062d\u0635\u0627\u0626\u064a\u0627\u062a',
      Status: '\u0627\u0644\u062d\u0627\u0644\u0629',
      'Sticky Actions': '\u0625\u062c\u0631\u0627\u0621\u0627\u062a \u0644\u0627\u0635\u0642\u0629',
      'Store Details': '\u062a\u0641\u0627\u0635\u064a\u0644 \u0627\u0644\u0645\u062a\u062c\u0631',
      Submit: '\u0625\u0631\u0633\u0627\u0644',
      Support: '\u0627\u0644\u062f\u0639\u0645',
      SweetAlert2: '\u0633\u0648\u064a\u062a \u0627\u0644\u064a\u0631\u062a 2',
      Switches: '\u0627\u0644\u0645\u0641\u0627\u062a\u064a\u062d',
      Tabler: '\u0637\u0627\u0648\u0644\u0629',
      Tables: '\u0627\u0644\u062c\u062f\u0627\u0648\u0644',
      'Tabs & Pills': '\u0623\u0644\u0633\u0646\u0629 \u0648\u0623\u0642\u0631\u0627\u0635',
      Teams: '\u0627\u0644\u0641\u0631\u0642',
      Template: '\u0642\u0627\u0644\u0628',
      'Text Divider': '\u0645\u0642\u0633\u0645 \u0627\u0644\u0646\u0635',
      Timeline: '\u0627\u0644\u062c\u062f\u0648\u0644 \u0627\u0644\u0632\u0645\u0646\u064a',
      Toasts: '\u0631\u0633\u0627\u0626\u0644 \u062a\u0646\u0628\u064a\u0647',
      'Tooltips & Popovers': '\u062a\u0644\u0645\u064a\u062d\u0627\u062a \u0648\u062a\u0644\u0642\u064a\u0627\u062a',
      Tour: '\u062c\u0648\u0644\u0629',
      Treeview: '\u0639\u0631\u0636 \u0627\u0644\u0634\u062c\u0631\u0629',
      'Two Steps': '\u062e\u0637\u0648\u062a\u064a\u0646',
      Typography: '\u0627\u0644\u0623\u0633\u0644\u0648\u0628 \u0627\u0644\u0637\u0628\u0627\u0639\u064a',
      'Under Maintenance': '\u062a\u062d\u062a \u0627\u0644\u0635\u064a\u0627\u0646\u0629',
      User: '\u0627\u0644\u0645\u0633\u062a\u062e\u062f\u0645',
      'User Management': '\u0625\u062f\u0627\u0631\u0629 \u0627\u0644\u0645\u0633\u062a\u062e\u062f\u0645\u064a\u0646',
      'User Profile':
        '\u0627\u0644\u0645\u0644\u0641 \u0627\u0644\u0634\u062e\u0635\u064a \u0644\u0644\u0645\u0633\u062a\u062e\u062f\u0645',
      'User Role': '\u062f\u0648\u0631 \u0627\u0644\u0645\u0633\u062a\u062e\u062f\u0645',
      'User interface': '\u0648\u0627\u062c\u0647\u0629 \u0627\u0644\u0645\u0633\u062a\u062e\u062f\u0645',
      Users: '\u0627\u0644\u0645\u0633\u062a\u062e\u062f\u0645\u064a\u0646',
      'Users & Settings':
        '\u0627\u0644\u0625\u0639\u062f\u0627\u062f\u0627\u062a & \u0627\u0644\u0645\u0633\u062a\u062e\u062f\u0645\u064a\u0646',
      'Verify Email':
        '\u0627\u0644\u062a\u062d\u0642\u0642 \u0645\u0646 \u0627\u0644\u0628\u0631\u064a\u062f \u0627\u0644\u0625\u0644\u0643\u062a\u0631\u0648\u0646\u064a',
      Vertical: '\u0631\u0623\u0633\u064a',
      'Vertical Form': '\u0646\u0645\u0648\u0630\u062c \u0639\u0645\u0648\u062f\u064a',
      View: '\u0639\u0631\u0636',
      'Without menu': '\u0628\u062f\u0648\u0646 \u0642\u0627\u0626\u0645\u0629',
      'Without navbar': '\u0628\u062f\u0648\u0646 \u0634\u0631\u064a\u0637 \u0627\u0644\u062a\u0646\u0642\u0644',
      'Wizard Examples': '\u0623\u0645\u062b\u0644\u0629 \u0639\u0644\u0649 \u0627\u0644\u0633\u0627\u062d\u0631',
      'add new role': '\u0623\u0636\u0641 \u062f\u0648\u0631\u064b\u0627 \u062c\u062f\u064a\u062f\u064b\u0627',
      eCommerce:
        '\u0627\u0644\u062a\u062c\u0627\u0631\u0629 \u0627\u0644\u0625\u0644\u0643\u062a\u0631\u0648\u0646\u064a\u0629',
      guard: '\u0627\u0644\u062d\u0627\u0631\u0633',
      permissions: '\u0627\u0644\u0635\u0644\u0627\u062d\u064a\u0627\u062a',
      role: '\u0627\u0644\u062f\u0648\u0631',
      'role name': '\u0627\u0633\u0645 \u0627\u0644\u062f\u0648\u0631'
    },
    'de.strings': {
      Academy: 'Akademie',
      Accordion: 'Akkordeon',
      Account: 'Konto',
      'Account Settings': 'Account Einstellungen',
      Actions: 'Aktionen',
      Add: 'Hinzuf\u00fcgen',
      'Add Product': 'Produkt hinzuf\u00fcgen',
      'Address & Billing': 'Adresse & Rechnungsstellung',
      Advance: 'Vorantreiben',
      Advanced: 'Fortgeschritten',
      Alerts: 'Warnungen',
      'All Customer': 'Alle Kunden',
      'All Customers': 'Alle Kunden',
      Analytics: 'Analytik',
      'Apex Charts': 'Apex-Diagramme',
      'App Brand': 'App-Marke',
      Apps: 'Anwendungen',
      'Apps & Pages': 'Apps und Seiten',
      Article: 'Artikel',
      Authentications: 'Authentifizierung',
      Avatar: 'Benutzerbild',
      Badges: 'Abzeichen',
      Basic: 'Basic',
      'Basic Inputs': 'Grundlegende Eingaben',
      'Billing & Plans': 'Abrechnung & Pl\u00e4ne',
      Blank: 'Leer',
      BlockUI: 'BlockUI',
      Buttons: 'Tasten',
      CRM: 'CRM',
      Calendar: 'Kalender',
      Cards: 'Karten',
      Carousel: 'Karussell',
      Categories: 'Kategorien',
      Category: 'Kategorie',
      'Category List': 'Kategorieliste',
      ChartJS: 'ChartJS',
      Charts: 'Diagramme',
      'Charts & Maps': 'Diagramme und Karten',
      Chat: 'Plaudern',
      Checkout: 'Auschecken',
      Collapse: 'Zusammenbruch',
      'Collapsed menu': 'Reduziertes Men\u00fc',
      'Coming Soon': 'Demn\u00e4chst',
      Components: 'Komponenten',
      Connections: 'Anschl\u00fcsse',
      Container: 'Container',
      'Content nav + Sidebar': 'Inhaltsnavigation + Seitenleiste',
      'Content navbar': 'Inhaltsnavigationsleiste',
      'Course Details': 'Kursdetails',
      Cover: 'Startseite',
      'Create Deal': 'Deal erstellen',
      'Custom Options': 'Benutzerdefinierte Optionen',
      Customer: 'Kunden',
      'Customer Details': 'Kundendetails',
      Dashboard: 'Armaturenbrett',
      Dashboards: 'Instrumententafel',
      Datatables: 'Datentabellen',
      Documentation: 'Dokumentation',
      'Drag & Drop': 'Ziehen und loslassen',
      Dropdowns: 'Dropdowns',
      Edit: 'Bearbeiten',
      Editors: 'Redakteure',
      Email: 'Email',
      Error: 'Error',
      'Extended UI': 'Erweiterte Benutzeroberfl\u00e4che',
      Extensions: 'Erweiterungen',
      Extras: 'Extras',
      FAQ: 'FAQ',
      'File Upload': 'Datei-Upload',
      Fleet: 'Flotte',
      Fluid: 'Fl\u00fcssigkeit',
      Fontawesome: 'Fontawesome',
      Footer: 'Fusszeile',
      'Forgot Password': 'Passwort vergessen',
      'Form Elements': 'Formularelemente',
      'Form Layouts': 'Formularlayouts',
      'Form Validation': 'Formularvalidierung',
      'Form Wizard': 'Formzauberer',
      Forms: 'Formen',
      'Forms & Tables': 'Formulare und Tabellen',
      'Front Pages': 'Vorderseiten',
      Fullscreen: 'Vollbildschirm',
      'Help Center': 'Hilfezentrum',
      Horizontal: 'Horizontal',
      'Horizontal Form': 'Horizontale Form',
      Icons: 'Symbole',
      'Input groups': 'Eingabegruppen',
      Invoice: 'Rechnung',
      Kanban: 'Schild',
      Landing: 'Landung',
      'Laravel Example': 'Laravel Beispiel',
      Layouts: 'Layouts',
      'Leaflet Maps': 'Faltblatt Karten',
      List: 'Liste',
      'List Groups': 'Gruppen auflisten',
      Locations: 'Standorte',
      Login: 'Anmeldung',
      Logistics: 'Logistik',
      'Manage Reviews': 'Bewertungen verwalten',
      'Media Player': 'Media Player',
      Menu: 'Speisekarte',
      Misc: 'Sonstiges',
      Miscellaneous: 'Sonstiges',
      'Modal Examples': 'Modale Beispiele',
      Modals: 'Modale',
      'Multi-steps': 'Mehrstufig',
      'My Course': 'Mein Kurs',
      Navbar: 'Navbar',
      'Not Authorized': 'Nicht berechtigt',
      Notifications: 'Benachrichtigungen',
      Numbered: 'Nummeriert',
      Offcanvas: 'Offcanvas',
      Order: 'Bestellungen',
      'Order Details': 'Bestelldetails',
      'Order List': 'Bestellungsliste',
      Overview: '\u00dcbersicht',
      Pages: 'Seiten',
      'Pagination & Breadcrumbs': 'Paginierung und Breadcrumbs',
      Payment: 'Zahlung',
      Payments: 'Zahlungen',
      'Perfect Scrollbar': 'Perfekte Bildlaufleiste',
      Permission: 'Genehmigung',
      Pickers: 'Pfl\u00fccker',
      Preview: 'Vorschau',
      Pricing: 'Preisgestaltung',
      'Product List': 'Produktliste',
      Products: 'Produkte',
      Profile: 'Profil',
      Progress: 'Fortschritt',
      Projects: 'Projekte',
      'Property Listing': 'Immobilienliste',
      Referrals: 'Empfehlungen verwalten',
      Register: 'Registrieren',
      'Reset Password': 'Passwort zur\u00fccksetzen',
      Roles: 'Rollen',
      'Roles & Permissions': 'Rollen & Berechtigungen',
      Security: 'Sicherheit',
      'Select & Tags': 'W\u00e4hlen Sie & Tags aus',
      Settings: 'Einstellungen',
      'Shipping & Delivery': 'Versand & Lieferung',
      Sliders: 'Schieberegler',
      Spinners: 'Spinner',
      'Star Ratings': 'Sternebewertung',
      Statistics: 'Statistiken',
      'Sticky Actions': 'Sticky-Aktionen',
      'Store Details': 'Gesch\u00e4ftsdetails',
      Support: 'Unterst\u00fctzung',
      SweetAlert2: 'SweetAlert2',
      Switches: 'Schalter',
      Tabler: 'Tisch',
      Tables: 'Tabellen',
      'Tabs & Pills': 'Tabs & Pillen',
      Teams: 'Mannschaften',
      'Text Divider': 'Textteiler',
      Timeline: 'Zeitleiste',
      Toasts: 'Toast',
      'Tooltips & Popovers': 'QuickInfos und Popovers',
      Tour: 'Tour',
      Treeview: 'Baumsicht',
      'Two Steps': 'Zwei schritte',
      Typography: 'Typografie',
      'Under Maintenance': 'Wird gewartet',
      'User Management': 'Benutzerverwaltung',
      'User Profile': 'Benutzerprofil',
      'User interface': 'Benutzeroberfl\u00e4che',
      Users: 'Benutzer',
      'Verify Email': 'E-Mail best\u00e4tigen',
      Vertical: 'Vertikal',
      'Vertical Form': 'Vertikale Form',
      View: 'Aussicht',
      'Without menu': 'Ohne Men\u00fc',
      'Without navbar': 'Ohne Navigationsleiste',
      'Wizard Examples': 'Wizard-Beispiele',
      eCommerce: 'E-Commerce'
    },
    'en.auth': {
      failed: 'These credentials do not match our records.',
      password: 'The provided password is incorrect.',
      throttle: 'Too many login attempts. Please try again in :seconds seconds.'
    },
    'en.pagination': { next: 'Next &raquo;', previous: '&laquo; Previous' },
    'en.passwords': {
      reset: 'Your password has been reset.',
      sent: 'We have emailed your password reset link.',
      throttled: 'Please wait before retrying.',
      token: 'This password reset token is invalid.',
      user: "We can't find a user with that email address."
    },
    'en.strings': {
      Academy: 'Academy',
      Accordion: 'Accordion',
      Account: 'Account',
      'Account Settings': 'Account Settings',
      Actions: 'Actions',
      'Active Users': 'Active Users',
      Add: 'Add',
      'Add New Role': 'Add New Role',
      'Add New User': 'Add New User',
      'Add Product': 'Add Product',
      'Add new roles with customized permissions as per your requirement':
        'Add new roles with customized permissions as per your requirement',
      'Address & Billing': 'Address & Billing',
      Administrator: 'Administrator',
      Advance: 'Advance',
      Advanced: 'Advanced',
      Alerts: 'Alerts',
      'All Customer': 'All Customer',
      'All Customers': 'All Customers',
      Analytics: 'Analytics',
      'Apex Charts': 'Apex Charts',
      'App Brand': 'App Brand',
      Apps: 'Apps',
      'Apps & Pages': 'Apps & Pages',
      Article: 'Article',
      Authentications: 'Authentications',
      Avatar: 'Avatar',
      Badges: 'Badges',
      Basic: 'Basic',
      'Basic Inputs': 'Basic Inputs',
      'Billing & Plans': 'Billing & Plans',
      Blank: 'Blank',
      BlockUI: 'BlockUI',
      Buttons: 'Buttons',
      CRM: 'CRM',
      Calendar: 'Calendar',
      Cards: 'Cards',
      Carousel: 'Carousel',
      Categories: 'Categories',
      Category: 'Category',
      'Category List': 'Category List',
      ChartJS: 'ChartJS',
      Charts: 'Charts',
      'Charts & Maps': 'Charts & Maps',
      Chat: 'Chat',
      Checkout: 'Checkout',
      Close: 'Close',
      Collapse: 'Collapse',
      'Collapsed menu': 'Collapsed menu',
      'Coming Soon': 'Coming Soon',
      Components: 'Components',
      'Confirm Password': 'Confirm Password',
      Connections: 'Connections',
      Container: 'Container',
      'Content nav + Sidebar': 'Content nav + Sidebar',
      'Content navbar': 'Content navbar',
      'Course Details': 'Course Details',
      Cover: 'Cover',
      'Create Deal': 'Create Deal',
      'Created At': 'Created At',
      'Custom Options': 'Custom Options',
      Customer: 'Customer',
      'Customer Details': 'Customer Details',
      Dashboard: 'Dashboard',
      Dashboards: 'Dashboards',
      Datatables: 'Datatables',
      Documentation: 'Documentation',
      'Donut drag\u00e9e jelly pie halvah. Danish gingerbread bonbon cookie wafer candy oat cake ice cream. Gummies halvah tootsie roll muffin biscuit icing dessert gingerbread. Pastry ice cream cheesecake fruitcake.':
        'Donut drag\u00e9e jelly pie halvah. Danish gingerbread bonbon cookie wafer candy oat cake ice cream. Gummies halvah tootsie roll muffin biscuit icing dessert gingerbread. Pastry ice cream cheesecake fruitcake.',
      'Drag & Drop': 'Drag & Drop',
      Driver: 'Driver',
      Dropdowns: 'Dropdowns',
      Edit: 'Edit',
      Editors: 'Editors',
      Email: 'Email',
      'Enter phone number': 'Enter phone number',
      Error: 'Error',
      'Extended UI': 'Extended UI',
      Extensions: 'Extensions',
      Extras: 'Extras',
      FAQ: 'FAQ',
      'File Upload': 'File Upload',
      Fleet: 'Fleet',
      Fluid: 'Fluid',
      Fontawesome: 'Fontawesome',
      Footer: 'Footer',
      'Forgot Password': 'Forgot Password',
      'Form Elements': 'Form Elements',
      'Form Layouts': 'Form Layouts',
      'Form Validation': 'Form Validation',
      'Form Wizard': 'Form Wizard',
      Forms: 'Forms',
      'Forms & Tables': 'Forms & Tables',
      'Front Pages': 'Front Pages',
      'Full Name': 'Full Name',
      Fullscreen: 'Fullscreen',
      General: 'General',
      'Help Center': 'Help Center',
      Home: 'Home',
      Horizontal: 'Horizontal',
      'Horizontal Form': 'Horizontal Form',
      Icons: 'Icons',
      Id: 'Id',
      'Inactive Users': 'Inactive Users',
      'Input groups': 'Input groups',
      Invoice: 'Invoice',
      'Jelly-o jelly beans icing pastry cake cake lemon drops. Muffin muffin pie tiramisu halvah cotton candy liquorice caramels.':
        'Jelly-o jelly beans icing pastry cake cake lemon drops. Muffin muffin pie tiramisu halvah cotton candy liquorice caramels.',
      Kanban: 'Kanban',
      Landing: 'Landing',
      'Laravel Example': 'Laravel Example',
      Layouts: 'Layouts',
      'Leaflet Maps': 'Leaflet Maps',
      List: 'List',
      'List Groups': 'List Groups',
      Locations: 'Locations',
      Login: 'Login',
      Logistics: 'Logistics',
      'Manage Reviews': 'Manage Reviews',
      'Media Player': 'Media Player',
      Menu: 'Menu',
      Messages: 'Messages',
      Misc: 'Misc',
      Miscellaneous: 'Miscellaneous',
      'Modal Examples': 'Modal Examples',
      Modals: 'Modals',
      'Multi-steps': 'Multi-steps',
      'My Course': 'My Course',
      Navbar: 'Navbar',
      'Not Authorized': 'Not Authorized',
      Notifications: 'Notifications',
      Numbered: 'Numbered',
      Offcanvas: 'Offcanvas',
      Order: 'Order',
      'Order Details': 'Order Details',
      'Order List': 'Order List',
      Overview: 'Overview',
      Pages: 'Pages',
      'Pagination & Breadcrumbs': 'Pagination & Breadcrumbs',
      Password: 'Password',
      Payment: 'Payment',
      Payments: 'Payments',
      'Pending Users': 'Pending Users',
      'Perfect Scrollbar': 'Perfect Scrollbar',
      Phone: 'Phone',
      Pickers: 'Pickers',
      Preview: 'Preview',
      Pricing: 'Pricing',
      'Product List': 'Product List',
      Products: 'Products',
      Profile: 'Profile',
      Progress: 'Progress',
      Projects: 'Projects',
      'Property Listing': 'Property Listing',
      Referrals: 'Referrals',
      Register: 'Register',
      'Reset Password': 'Reset Password',
      Role: 'Role',
      'Roles & Permissions': 'Roles & Permissions',
      Security: 'Security',
      'Select & Tags': 'Select & Tags',
      Settings: 'Settings',
      'Shipping & Delivery': 'Shipping & Delivery',
      Sliders: 'Sliders',
      Spinners: 'Spinners',
      'Star Ratings': 'Star Ratings',
      Statistics: 'Statistics',
      Status: 'Status',
      'Sticky Actions': 'Sticky Actions',
      'Store Details': 'Store Details',
      Submit: 'Submit',
      Support: 'Support',
      SweetAlert2: 'SweetAlert2',
      Switches: 'Switches',
      Tabler: 'Tabler',
      Tables: 'Tables',
      'Tabs & Pills': 'Tabs & Pills',
      Teams: 'Teams',
      Template: 'Template',
      'Text Divider': 'Text Divider',
      Timeline: 'Timeline',
      Toasts: 'Toasts',
      'Tooltips & Popovers': 'Tooltips & Popovers',
      Tour: 'Tour',
      Treeview: 'Treeview',
      'Two Steps': 'Two Steps',
      Typography: 'Typography',
      'Under Maintenance': 'Under Maintenance',
      User: 'User',
      'User Management': 'User Management',
      'User Profile': 'User Profile',
      'User Role': 'User Role',
      'User interface': 'User interface',
      Users: 'Users',
      'Verify Email': 'Verify Email',
      Vertical: 'Vertical',
      'Vertical Form': 'Vertical Form',
      View: 'View',
      'Without menu': 'Without menu',
      'Without navbar': 'Without navbar',
      'Wizard Examples': 'Wizard Examples',
      'add new role': 'add new role',
      eCommerce: 'eCommerce',
      guard: 'guard',
      permissions: 'permissions',
      role: 'role',
      'role name': 'role name'
    },
    'en.validation': {
      accepted: 'The :attribute field must be accepted.',
      accepted_if: 'The :attribute field must be accepted when :other is :value.',
      active_url: 'The :attribute field must be a valid URL.',
      after: 'The :attribute field must be a date after :date.',
      after_or_equal: 'The :attribute field must be a date after or equal to :date.',
      alpha: 'The :attribute field must only contain letters.',
      alpha_dash: 'The :attribute field must only contain letters, numbers, dashes, and underscores.',
      alpha_num: 'The :attribute field must only contain letters and numbers.',
      array: 'The :attribute field must be an array.',
      ascii: 'The :attribute field must only contain single-byte alphanumeric characters and symbols.',
      attributes: [],
      before: 'The :attribute field must be a date before :date.',
      before_or_equal: 'The :attribute field must be a date before or equal to :date.',
      between: {
        array: 'The :attribute field must have between :min and :max items.',
        file: 'The :attribute field must be between :min and :max kilobytes.',
        numeric: 'The :attribute field must be between :min and :max.',
        string: 'The :attribute field must be between :min and :max characters.'
      },
      boolean: 'The :attribute field must be true or false.',
      confirmed: 'The :attribute field confirmation does not match.',
      current_password: 'The password is incorrect.',
      custom: { 'attribute-name': { 'rule-name': 'custom-message' } },
      date: 'The :attribute field must be a valid date.',
      date_equals: 'The :attribute field must be a date equal to :date.',
      date_format: 'The :attribute field must match the format :format.',
      decimal: 'The :attribute field must have :decimal decimal places.',
      declined: 'The :attribute field must be declined.',
      declined_if: 'The :attribute field must be declined when :other is :value.',
      different: 'The :attribute field and :other must be different.',
      digits: 'The :attribute field must be :digits digits.',
      digits_between: 'The :attribute field must be between :min and :max digits.',
      dimensions: 'The :attribute field has invalid image dimensions.',
      distinct: 'The :attribute field has a duplicate value.',
      doesnt_end_with: 'The :attribute field must not end with one of the following: :values.',
      doesnt_start_with: 'The :attribute field must not start with one of the following: :values.',
      email: 'The :attribute field must be a valid email address.',
      ends_with: 'The :attribute field must end with one of the following: :values.',
      enum: 'The selected :attribute is invalid.',
      exists: 'The selected :attribute is invalid.',
      file: 'The :attribute field must be a file.',
      filled: 'The :attribute field must have a value.',
      gt: {
        array: 'The :attribute field must have more than :value items.',
        file: 'The :attribute field must be greater than :value kilobytes.',
        numeric: 'The :attribute field must be greater than :value.',
        string: 'The :attribute field must be greater than :value characters.'
      },
      gte: {
        array: 'The :attribute field must have :value items or more.',
        file: 'The :attribute field must be greater than or equal to :value kilobytes.',
        numeric: 'The :attribute field must be greater than or equal to :value.',
        string: 'The :attribute field must be greater than or equal to :value characters.'
      },
      image: 'The :attribute field must be an image.',
      in: 'The selected :attribute is invalid.',
      in_array: 'The :attribute field must exist in :other.',
      integer: 'The :attribute field must be an integer.',
      ip: 'The :attribute field must be a valid IP address.',
      ipv4: 'The :attribute field must be a valid IPv4 address.',
      ipv6: 'The :attribute field must be a valid IPv6 address.',
      json: 'The :attribute field must be a valid JSON string.',
      lowercase: 'The :attribute field must be lowercase.',
      lt: {
        array: 'The :attribute field must have less than :value items.',
        file: 'The :attribute field must be less than :value kilobytes.',
        numeric: 'The :attribute field must be less than :value.',
        string: 'The :attribute field must be less than :value characters.'
      },
      lte: {
        array: 'The :attribute field must not have more than :value items.',
        file: 'The :attribute field must be less than or equal to :value kilobytes.',
        numeric: 'The :attribute field must be less than or equal to :value.',
        string: 'The :attribute field must be less than or equal to :value characters.'
      },
      mac_address: 'The :attribute field must be a valid MAC address.',
      max: {
        array: 'The :attribute field must not have more than :max items.',
        file: 'The :attribute field must not be greater than :max kilobytes.',
        numeric: 'The :attribute field must not be greater than :max.',
        string: 'The :attribute field must not be greater than :max characters.'
      },
      max_digits: 'The :attribute field must not have more than :max digits.',
      mimes: 'The :attribute field must be a file of type: :values.',
      mimetypes: 'The :attribute field must be a file of type: :values.',
      min: {
        array: 'The :attribute field must have at least :min items.',
        file: 'The :attribute field must be at least :min kilobytes.',
        numeric: 'The :attribute field must be at least :min.',
        string: 'The :attribute field must be at least :min characters.'
      },
      min_digits: 'The :attribute field must have at least :min digits.',
      missing: 'The :attribute field must be missing.',
      missing_if: 'The :attribute field must be missing when :other is :value.',
      missing_unless: 'The :attribute field must be missing unless :other is :value.',
      missing_with: 'The :attribute field must be missing when :values is present.',
      missing_with_all: 'The :attribute field must be missing when :values are present.',
      multiple_of: 'The :attribute field must be a multiple of :value.',
      not_in: 'The selected :attribute is invalid.',
      not_regex: 'The :attribute field format is invalid.',
      numeric: 'The :attribute field must be a number.',
      password: {
        letters: 'The :attribute field must contain at least one letter.',
        mixed: 'The :attribute field must contain at least one uppercase and one lowercase letter.',
        numbers: 'The :attribute field must contain at least one number.',
        symbols: 'The :attribute field must contain at least one symbol.',
        uncompromised: 'The given :attribute has appeared in a data leak. Please choose a different :attribute.'
      },
      present: 'The :attribute field must be present.',
      prohibited: 'The :attribute field is prohibited.',
      prohibited_if: 'The :attribute field is prohibited when :other is :value.',
      prohibited_unless: 'The :attribute field is prohibited unless :other is in :values.',
      prohibits: 'The :attribute field prohibits :other from being present.',
      regex: 'The :attribute field format is invalid.',
      required: 'The :attribute field is required.',
      required_array_keys: 'The :attribute field must contain entries for: :values.',
      required_if: 'The :attribute field is required when :other is :value.',
      required_if_accepted: 'The :attribute field is required when :other is accepted.',
      required_unless: 'The :attribute field is required unless :other is in :values.',
      required_with: 'The :attribute field is required when :values is present.',
      required_with_all: 'The :attribute field is required when :values are present.',
      required_without: 'The :attribute field is required when :values is not present.',
      required_without_all: 'The :attribute field is required when none of :values are present.',
      same: 'The :attribute field must match :other.',
      size: {
        array: 'The :attribute field must contain :size items.',
        file: 'The :attribute field must be :size kilobytes.',
        numeric: 'The :attribute field must be :size.',
        string: 'The :attribute field must be :size characters.'
      },
      starts_with: 'The :attribute field must start with one of the following: :values.',
      string: 'The :attribute field must be a string.',
      timezone: 'The :attribute field must be a valid timezone.',
      ulid: 'The :attribute field must be a valid ULID.',
      unique: 'The :attribute has already been taken.',
      uploaded: 'The :attribute failed to upload.',
      uppercase: 'The :attribute field must be uppercase.',
      url: 'The :attribute field must be a valid URL.',
      uuid: 'The :attribute field must be a valid UUID.'
    },
    'fr.strings': {
      Academy: 'Acad\u00e9mie',
      Accordion: 'Accord\u00e9on',
      Account: 'Compte',
      'Account Settings': 'Param\u00e8tres du compte',
      Actions: 'Actions',
      Add: 'Ajouter',
      'Add Product': 'Ajouter un produit',
      'Address & Billing': 'Adresse et facturation',
      Advance: 'Avance',
      Advanced: 'Avanc\u00e9e',
      Alerts: 'Alertes',
      'All Customer': 'Tous les clients',
      'All Customers': 'Tous les clients',
      Analytics: 'Analytique',
      'Apex Charts': 'Graphiques Apex',
      'App Brand': "Marque d'application",
      Apps: 'Applications',
      'Apps & Pages': 'Applications et pages',
      Article: 'Article',
      Authentications: 'Authentification',
      Avatar: 'Avatar',
      Badges: 'Badges',
      Basic: 'De base',
      'Basic Inputs': 'Entr\u00e9es de base',
      'Billing & Plans': 'Facturation et forfaits',
      Blank: 'Vide',
      BlockUI: 'BlockUI',
      Buttons: 'Boutons',
      CRM: 'GRC',
      Calendar: 'Calendrier',
      Cards: 'Cartes',
      Carousel: 'Carrousel',
      Categories: 'Cat\u00e9gories',
      Category: 'Cat\u00e9gorie',
      'Category List': 'Liste de cat\u00e9gories',
      ChartJS: 'ChartJS',
      Charts: 'Graphiques',
      'Charts & Maps': 'Graphiques et cartes',
      Chat: 'Discuter',
      Checkout: 'V\u00e9rifier',
      Collapse: 'Effondrer',
      'Collapsed menu': 'Menu r\u00e9duit',
      'Coming Soon': 'Bient\u00f4t disponible',
      Components: 'Composants',
      Connections: 'Connexions',
      Container: 'R\u00e9cipient',
      'Content nav + Sidebar': 'Navigation dans le contenu + barre lat\u00e9rale',
      'Content navbar': 'Barre de navigation du contenu',
      'Course Details': 'D\u00e9tails du cours',
      Cover: 'Couverture',
      'Create Deal': 'Cr\u00e9er une offre',
      'Custom Options': 'Options personnalis\u00e9es',
      Customer: 'Client',
      'Customer Details': 'D\u00e9tails du client',
      Dashboard: 'Tableau de bord',
      Dashboards: 'Tableaux de bord',
      Datatables: 'Tables de donn\u00e9es',
      Documentation: 'Documentation',
      'Drag & Drop': 'Glisser-d\u00e9poser',
      Dropdowns: 'Dropdowns',
      Edit: '\u00c9diter',
      Editors: 'Editeurs',
      Email: 'Email',
      Error: 'Erreur',
      'Extended UI': 'UI \u00e9tendue',
      Extensions: 'Extensions',
      Extras: 'Suppl\u00e9ments',
      FAQ: 'FAQ',
      'File Upload': 'T\u00e9l\u00e9chargement de fichiers',
      Fleet: 'Flotte',
      Fluid: 'Fluide',
      Fontawesome: 'Fontawesome',
      Footer: 'Bas de page',
      'Forgot Password': 'Mot de passe oubli\u00e9',
      'Form Elements': 'El\u00e9ments de formulaire',
      'Form Layouts': 'Disposition des formulaires',
      'Form Validation': 'Validation de formulaire',
      'Form Wizard': 'Assistant Formulaire',
      Forms: 'Formes',
      'Forms & Tables': 'Formulaires et tableaux',
      'Front Pages': 'Premi\u00e8re page',
      Fullscreen: 'Plein \u00e9cran',
      'Help Center': "Centre d'aide",
      Horizontal: 'Horizontal',
      'Horizontal Form': 'Forme horizontale',
      Icons: 'Ic\u00f4nes',
      'Input groups': "Groupes d'entr\u00e9e",
      Invoice: "Facture d'achat",
      Kanban: 'Enseigne',
      Landing: 'Atterrissage',
      'Laravel Examples': 'Exemples de Laravel',
      Layouts: 'Dispositions',
      'Leaflet Maps': 'D\u00e9pliant Cartes',
      List: 'Liste',
      'List Groups': 'Liste des groupes',
      Locations: 'Emplacements',
      Login: 'Connexion',
      Logistics: 'Logistique',
      'Manage Reviews': 'G\u00e9rer les avis',
      'Media Player': 'Lecteur multim\u00e9dia',
      Menu: 'Menu',
      Misc: 'Divers',
      Miscellaneous: 'Divers',
      'Modal Examples': 'Exemples modaux',
      Modals: 'Modaux',
      'Multi-steps': 'Multi-\u00e9tapes',
      'My Course': 'Mon cours',
      Navbar: 'Navbar',
      'Not Authorized': 'Pas autoris\u00e9',
      Notifications: 'Notifications',
      Numbered: 'Num\u00e9rot\u00e9e',
      Offcanvas: 'Hors-toile',
      Order: 'Commande',
      'Order Details': 'D\u00e9tails de la commande',
      'Order List': 'Liste des commandes',
      Overview: 'Aper\u00e7u',
      Pages: 'Pages',
      'Pagination & Breadcrumbs': "Pagination et fil d'Ariane",
      Payment: 'Paiement',
      Payments: 'Paiements',
      'Perfect Scrollbar': 'Barre de d\u00e9filement parfaite',
      Permission: 'Autorisation',
      Pickers: 'Cueilleurs',
      Preview: 'Aper\u00e7u',
      Pricing: 'Tarification',
      'Product List': 'Liste de produits',
      Products: 'Produits',
      Profile: 'Profil',
      Progress: 'Le progr\u00e8s',
      Projects: 'Projets',
      'Property Listing': 'Liste des propri\u00e9t\u00e9s',
      Referrals: 'Parrainages',
      Register: "S'inscrire",
      'Reset Password': 'r\u00e9initialiser le mot de passe',
      Roles: 'Les r\u00f4les',
      'Roles & Permissions': 'R\u00f4les et autorisations',
      Security: 'S\u00e9curit\u00e9',
      'Select & Tags': 'S\u00e9lectionner et balises',
      Settings: 'Param\u00e8tres',
      'Shipping & Delivery': 'Exp\u00e9dition et livraison',
      Sliders: 'Glissi\u00e8res',
      Spinners: 'Spinners',
      'Star Ratings': 'Classement par \u00e9toiles',
      Statistics: 'Statistiques',
      'Sticky Actions': 'Actions collantes',
      'Store Details': 'D\u00e9tails du magasin',
      Support: 'Soutien',
      SweetAlert2: 'SweetAlert2',
      Switches: 'Commutateurs',
      Tabler: 'Tableur',
      Tables: 'Les tables',
      'Tabs & Pills': 'Onglets et pilules',
      Teams: '\u00c9quipes',
      'Text Divider': 'S\u00e9parateur de texte',
      Timeline: 'Chronologie',
      Toasts: 'Toasts',
      'Tooltips & Popovers': 'Info-bulles et popovers',
      Tour: 'Tour',
      Treeview: 'Affichage arborescent',
      'Two Steps': 'Deux \u00e9tapes',
      Typography: 'Typographie',
      'Under Maintenance': 'En maintenance',
      'User Management': 'Gestion des utilisateurs',
      'User Profile': "Profil de l'utilisateur",
      'User interface': 'Interface utilisateur',
      Users: 'Utilisateurs',
      'Verify Email': 'V\u00e9rifier les courriels',
      Vertical: 'Vertical',
      'Vertical Form': 'Forme verticale',
      View: 'Vue',
      'Without menu': 'Sans menu',
      'Without navbar': 'Sans barre de navigation',
      'Wizard Examples': "Exemples d'assistant",
      eCommerce: 'Commerce \u00e9lectronique'
    }
  });
})();
