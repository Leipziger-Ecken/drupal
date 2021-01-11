(function (Drupal) {
  'use strict';

  function init(context) {
    var elements = context.querySelectorAll('[data-mail-to]');
    var clickable = context.querySelectorAll('[data-mail-click-link]');

    if (!elements) {
      return;
    }

    /**
     * Shift the string with rot 13.
     * Rewrite from Jonas Raoni Soares Silva
     *
     * @see http://jsfromhell.com/string/rot13 [rev. #1]
     *
     * @param string
     * @returns string
     */
    function rot13(string) {
      return string.replace(/[a-zA-Z]/g, function (c) {
        return String.fromCharCode((c <= 'Z' ? 90 : 122) >= (c = c.charCodeAt(0) + 13) ? c : c - 26);
      });
    }

    /**
     * Decrypt the string to an email.
     *
     * @param string
     * @returns string
     */
    function normalizeEncryptEmail(string) {
      string = rot13(string);
      string = string.replace(/\/dot\//g, '.');
      string = string.replace(/\/at\//g, '@');

      return string;
    }

    function setMailAddress(element) {
      var mailTo = normalizeEncryptEmail(element.getAttribute('data-mail-to'));
      var replaceInner = element.getAttribute('data-replace-inner');

      element.removeAttribute('data-mail-to');
      element.removeAttribute('data-replace-inner');

      // set href if anchor tag
      if (element.tagName === 'A') {
        element.setAttribute('href', 'mailto:' + mailTo);
      }

      // replace hole string
      if (replaceInner === 'true' || replaceInner === '') {
        element.innerHTML = mailTo;

        return;
      }

      // replace the token given in [data-replace-inner]
      if (replaceInner) {
        element.innerHTML = element.innerHTML.replace(replaceInner, mailTo);
      }
    }

    if (clickable.length) {
      clickable.forEach(function (element) {
        element.addEventListener('click', function (event) {
          if (element.className.split(/\s+/).indexOf('link-processed') === -1) {
            event.preventDefault();
            setMailAddress(element);
            element.classList.add('link-processed');
          }

        });
      });

      return;
    }

    NodeList.prototype.forEach = Array.prototype.forEach;

    elements.forEach(function (element) {
      setMailAddress(element);
    });
  }

  Drupal.behaviors.obfuscateEmailField = {
    attach: init
  };
})(Drupal);
