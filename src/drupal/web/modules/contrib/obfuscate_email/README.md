# Obfuscate Email for Drupal 8

## Source

GitHub: https://www.github.com/WondrousLLC/obfuscate_email

## What is this?

This module protects email addresses from spam bots, in two ways:

1. A field template
2. a text filter for ckeditor output.

Both situation utilize the same javascript.

## How it works?

To hide your emails from bots, render a non readable email on the server and
decrypt it via vanilla JS in the client. No jQuery needed. The
[basic idea](www.grall.name/posts/1/antiSpam-emailAddressObfuscation.html)
consists of three parts:

- hide behind a data-attribute
- substitute the @-sign and dots (.) with `/at/`, `/dot/`, then
- shift everything via [rot13](https://en.wikipedia.org/wiki/ROT13)
- rebuild it via javascript

**Notice**: There is no non-javascript fallback to this method!

## How to use the template 

Have a look into ``template/field--email.html.twig`` to have a fully working
example. This template will be used when the module is enabled. Use the
drupal suggestion system to override this default template. The JS is attached
to the page.

```
<a data-mail-to="znvy/ng/znvy/qbg/pbz">Email</a>
<a data-mail-to="znvy/ng/znvy/qbg/pbz" data-replace-inner="">Email</a>
<span data-mail-to="znvy/ng/znvy/qbg/pbz" data-replace-inner="@mail">drop me a line at @mail</span>
```

will be converted to

```
<a href="mailto:mail@mail.com">Email</a>
<a href="mailto:mail@mail.com">mail@mail.com</a>
<span>drop me a line at mail@mail.com</span>
```

Note: Use the `data-replace-inner` attribute to replace the complete inner text,
or give it a string to only replace this very string.

## How to use the text filter
 
In the backend go to `admin/config/content/formats`, e.g. Basic HTML, and under
the section "Enabled filters" check the box "Obfuscate Email". So now every
mailto-anchor written in the ckeditor will be preprocessed before rendering and
substituted on the client side.

