<?php

namespace Drupal\htmlmail\Utility;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Component\Utility\UrlHelper;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;

/**
 * @file
 * Provides the MailMIME class for creating MIME-formatted email messages.
 */


/**
 * The MailMIME class is used to create MIME email messages.
 *
 * The MailMIME class extends the PEAR Mail_Mime class as follows:
 * - All errors are routed to logger.
 * - Content-IDs are assigned based on filename, not the current timestamp.
 * - Only the first call to MailMIME::addHTMLImage() for a given filename will
 *   attach the file.  Subsequent calls with the same filename will return
 *   TRUE for success but will not attach additional copies.
 * - Image references within the HTML part are auto-detected and converted
 *   to inline attachments, as long as their URLs can be resolved to files
 *   within the current site.
 * - Public methods are named and documented according to Drupal standards.
 *
 * @see http://pear.php.net/package/Mail_mime
 */

require_once 'Mail/mime.php';
require_once 'Mail/mimeDecode.php';

/**
 * Class HTMLMailMime.
 *
 * @package Drupal\htmlmail\Utility
 */
class HTMLMailMime extends \Mail_mime {
  /**
   * Holds attached content-ids to to avoid attaching the same file twice.
   *
   * @var array
   */
  protected $cids = [];
  protected static $logger;
  protected static $mimeTypeGuesser;
  protected static $fileSystem;

  /**
   * Holds parameters used for building the formatted message.
   *
   * @var array
   *   An associative array of parameters containing the following:
   *   - head_encoding: The encoding to use for headers.  May be:
   *     - base64:
   *     - quoted-printable: (default)
   *   - text_encoding: The encoding to use for the text/plain part.  May be:
   *     - 7bit:
   *     - 8bit:
   *     - base64:
   *     - quoted-printable: (default)
   *   - html_encoding: The encoding to use for the text/html part.  May be:
   *     - 7bit:
   *     - 8bit:
   *     - base64:
   *     - quoted-printable: (default)
   *   - html_charset: The character set to use for the text/html part.
   *     Defaults to 'UTF-8'.
   *   - text_charset: The character set to use for the text/plain part.
   *     Defaults to 'UTF-8'.
   *   - head_charset: The character set to use for the header values.
   *     Defaults to 'UTF-8'.
   *   - eol: The end-of-line or line-ending sequence.  Defaults to an auto-
   *     detected value depending on the server operating system.  May be
   *     overridden by setting $config['mail_line_endings'].
   *   - delay_file_io: FALSE if attached files should be read immediately,
   *     rather than when the message is built.  Defaults to TRUE.
   */
  public $buildParams = [
    'head_encoding' => 'quoted-printable',
    'text_encoding' => '8bit',
    'html_encoding' => '8bit',
    'html_charset' => 'UTF-8',
    'text_charset' => 'UTF-8',
    'head_charset' => 'UTF-8',
    'eol' => NULL,
    'delay_file_io' => TRUE,
  ];

  /**
   * Routes PEAR_Error objects to logger.
   *
   * Passes PEAR_Error objects to logger, and returns FALSE.
   *
   * @param object $data
   *   The result of another function that may return a PEAR_Error object.
   *
   * @return bool
   *   FALSE if $data is a PEAR_Error object; otherwise $data.
   */
  protected static function &successful(&$data) {
    if (\PEAR::isError($data)) {

      self::getLogger()->error('<a href=":pear_error">PEAR error: @error</a>', [
        ':pear_error' => 'http://pear.php.net/manual/core.pear.pear.iserror.php',
        '@error' => $data->toString(),
      ]);

      $data = FALSE;
    }
    return $data;
  }

  /**
   * HTMLMailMime constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger service.
   * @param \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface $mimeTypeGuesser
   *   The mime type service.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The filesystem service.
   * @param array $params
   *   The params.
   */
  public function __construct(LoggerChannelFactoryInterface $logger, MimeTypeGuesserInterface $mimeTypeGuesser, FileSystemInterface $fileSystem, array $params = []) {
    self::$logger = $logger;
    self::$mimeTypeGuesser = $mimeTypeGuesser;
    self::$fileSystem = $fileSystem;
    parent::__construct($params);
  }

  /**
   * Retrieves the logger for mime.
   *
   * @return \Drupal\Core\Logger\LoggerChannelInterface
   *   The logger interface.
   */
  public static function getLogger() {
    return self::$logger->get('htmlmail_mime');
  }

  /**
   * Set the text/plain part of the message.
   *
   * @param string $data
   *   Either the text/plain data or the name of a file containing data.
   * @param bool $is_file
   *   (optional) TRUE if $data is a filename.  Defaults to FALSE.
   * @param bool $append
   *   (optional) TRUE to append the data to the exiting text/plain part, or
   *   FALSE to overwrite existing data.  Defaults to FALSE.
   *
   * @return bool
   *   TRUE if successful; otherwise FALSE.
   */
  public function setTxtBody($data, $is_file = FALSE, $append = FALSE) {
    $txt_body = parent::setTXTBody($data, $is_file, $append);
    return self::successful($txt_body);
  }

  /**
   * Sets the text/html part of the message.
   *
   * @param string $data
   *   Either the text/html data or the name of a file containing the data.
   * @param bool $is_file
   *   (optional) TRUE if $data is a filename.  Defaults to FALSE.
   *
   * @return bool
   *   TRUE if successful; otherwise FALSE.
   */
  public function setHtmlBody($data, $is_file = FALSE) {
    $html_body = parent::setHTMLBody($data, $is_file);
    return self::successful($html_body);
  }

  /**
   * Adds an image to the list of embedded images.
   *
   * @param string|array $file
   *   The image file name OR image data itself.
   * @param string $content_type
   *   (optional) The content-type of the image, such as "image/gif".
   * @param string $name
   *   (optional) The filename of the image, if $is_file is FALSE.
   * @param bool $is_file
   *   (optional) FALSE if $file contains the actual image data, rather than
   *   a filename.  Defaults to TRUE.
   * @param int $content_id
   *   (optional) The desired Content-ID for this MIME part.
   *
   * @return bool
   *   TRUE if the file was successfully attached, and FALSE if it wasn't.
   */
  public function addHtmlImage(
    $file,
    $content_type = NULL,
    $name = '',
    $is_file = TRUE,
    $content_id = NULL
  ) {
    $filename = $is_file ? $file : $name;
    if (empty($content_id)) {
      $content_id = md5($filename);
    }
    if (empty($content_type)) {
      $content_type = self::guessMimeType($filename);
    }
    if (!isset($this->cids[$content_id])) {
      $this->cids[$content_id] =
        self::successful(
          parent::addHTMLImage($file, $content_type, $name, $is_file, $content_id)
        );
    }
    return $this->cids[$content_id];
  }

  /**
   * Guess the content type of a file or inline data stream.
   *
   * Uses the Mime Detect module if available; otherwise uses the
   * file_get_mimetype() function.  Provides a smaller-than-default
   * mime-type mapping to improve performance a bit.
   *
   * @param string $filename
   *   The name of the file, used for guessing the mime type based on the
   *   filename extension.
   *
   * @return string
   *   The MIME content-type matching the file contents or filename extension,
   *   or "application/octet-stream" if no match could be found.
   */
  public static function guessMimeType($filename) {
    return self::$mimeTypeGuesser->guess($filename);
  }

  /**
   * Adds a file to the list of attachments.
   *
   * @param object $file
   *   The filename to attach, or the file contents itself.
   * @param string $content_type
   *   (optional) The content-type, such as 'application/x-pdf'.
   * @param string $name
   *   (optional) The filename of the attachment, if $is_file is FALSE.
   * @param bool $is_file
   *   (optional) FALSE if $file contains file data rather than a filename.
   *   Defaults to TRUE.
   * @param string $encoding
   *   (optional) The encoding to use for the file data. May be one of:
   *   - 7bit:
   *   - 8bit:
   *   - base64: (default)
   *   - quoted-printable.
   * @param string $disposition
   *   (optional) The content-disposition of this file.  May be one of:
   *   - attachment (default)
   *   - inline.
   * @param string $charset
   *   (optional) The character set of the attachment's content.
   * @param string $language
   *   (optional) The language of the attachment.
   * @param string $location
   *   (optional) The RFC 2557.4 location of the attachment.
   * @param string $name_encoding
   *   (optional) The encoding to use for the attachment name, instead of the
   *   default RFC2231 encoding.  May be one of:
   *   - base64
   *   - quoted-printable.
   * @param string $filename_encoding
   *   (optional) The encoding to use for the attachment filename, instead of
   *   the default RFC2231 encoding.  May be one of:
   *   - base64
   *   - quoted-printable.
   * @param string $description
   *   (optional) The value to use for the Content-Description header.
   * @param string $header_encoding
   *   (optional) The character set to use for this part's MIME headers.
   * @param array $add_header
   *   (optional) Extra headers.
   *
   * @return bool
   *   TRUE if successful; otherwise FALSE.
   */
  public function addAttachment(
    $file,
    $content_type = 'application/octet-stream',
    $name = '',
    $is_file = TRUE,
    $encoding = 'base64',
    $disposition = 'attachment',
    $charset = '',
    $language = '',
    $location = '',
    $name_encoding = NULL,
    $filename_encoding = NULL,
    $description = '',
    $header_encoding = NULL,
    array $add_header = []
  ) {
    // @todo Set content_type with mimedetect if possible.
    return self::successful(
      parent::addAttachment($file, $content_type, $name, $is_file, $encoding,
        $disposition, $charset, $language, $location, $name_encoding,
        $filename_encoding, $description, $header_encoding, $add_header)
    );
  }

  /**
   * Returns the complete e-mail, ready to send.
   *
   * @param string $separation
   *   (optional) The string used to separate header and body parts.
   * @param string $params
   *   (optional) Build parameters for the MailMimeInterface::get() method.
   * @param string $headers
   *   (optional) The extra headers that should be passed to the
   *   self::headers() method.
   * @param bool $overwrite
   *   TRUE if $headers parameter should overwrite previous data.
   *
   * @return string
   *   The complete message as a string if successful; otherwise FALSE.
   */
  public function getMessage($separation = NULL, $params = NULL, $headers = NULL, $overwrite = FALSE) {
    return self::successful(
      parent::getMessage($separation, $params, $headers, $overwrite)
    );
  }

  /**
   * Appends the complete e-mail to a file.
   *
   * @param string $filename
   *   The output file location.
   * @param string $params
   *   (optional) Build parameters for the MailMimeInterface::get() method.
   * @param string $headers
   *   (optional) The extra headers that should be passed to the
   *   MailMimeInterface::headers() method.
   * @param bool $overwrite
   *   TRUE if $headers parameter should overwrite previous data.
   *
   * @return string
   *   TRUE if successful; otherwise FALSE.
   */
  public function saveMessage($filename, $params = NULL, $headers = NULL, $overwrite = FALSE) {
    return self::successful(
      parent::saveMessage($filename, $params, $headers, $overwrite)
    );
  }

  /**
   * Appends the complete e-mail body to a file.
   *
   * @param string $filename
   *   The output file location.
   * @param string $params
   *   (optional) Build parameters for the MailMimeInterface::get() method.
   *
   * @return bool
   *   TRUE if successful; otherwise FALSE.
   */
  public function saveMessageBody($filename, $params = NULL) {
    return self::successful(
      parent::saveMessageBody($filename, $params)
    );
  }

  /**
   * A preg_replace_callback used to attach local files, if possible.
   *
   * @see get()
   */
  protected function attachRegex($matches) {
    if (($url = UrlHelper::stripDangerousProtocols($matches[4]))
      && ($path = self::$fileSystem->realpath($url))
      && is_file($path)
      && $this->addHtmlImage($path)
    ) {
      // The parent method will replace this with the actual cid: string.
      $matches[4] = $path;
    }
    return implode('', array_slice($matches, 1));
  }

  /**
   * Builds and returns the full multipart message with all its parts.
   *
   * Searches for inline file references and attaches local files, if possible.
   *
   * @param string $params
   *   (optional) An associative array used to override the
   *   HTMLMailMime::_build_params values for building this message.
   * @param string $filename
   *   (optional) The filename where the message data should be written. The
   *   default is to return the message data as a string.
   * @param bool $skip_head
   *   (optional) TRUE if only the message body should be generated.  Defaults
   *   to FALSE: return both headers and body together.
   *
   * @return mixed
   *   - FALSE: If an error occurred.
   *   - NULL: If $filename is set and no error occurred.
   *   - string: The formatted message if $filename is not set and no error
   *     occurred.
   */
  public function &get($params = NULL, $filename = NULL, $skip_head = FALSE) {
    if (isset($this->_htmlbody)) {
      $this->_htmlbody = preg_replace_callback(
        [
          '#(?<!\S)(src|background|href)\s*(=)\s*(["\'])(?!cid:)([^?]*?)(?<!\.css)(?<!\.js)(\?.*?)?(\3)(?=[ >])#i',
          '#(?<!\S)(url)\s*(\()\s*(["\'])(?!cid:)([^?]*?)(?<!\.css)(?<!\.js)(\?.*?)?(\3)(?=[ )])#i',
        ],
        [&$this, 'attachRegex'],
        $this->_htmlbody
      );
    }

    $get_return = parent::get($params, $filename, $skip_head);
    return self::successful($get_return);
  }

  /**
   * Encodes a header value as per RFC2047.
   *
   * @param string $name
   *   The header name.
   * @param string $value
   *   The header value to be encoded.
   * @param string $charset
   *   The character set name to be used, such as 'UTF-8' or 'ISO-8859-1'.
   * @param string $encoding
   *   The encoding name. Must be be one of:
   *   - base64:
   *   - quoted-printable.
   *
   * @return string
   *   The encoded header value (without a name)
   *
   * @see http://www.apps.ietf.org/rfc/rfc2047.html
   */
  public function mimeEncodeHeader($name, $value, $charset = 'UTF-8', $encoding = 'quoted-printable') {
    return parent::encodeHeader($name, $value, $charset, $encoding);
  }

  /**
   * Parse a complete message and return a MailMIME object.
   *
   * @param string $message
   *   The complete message, including headers and body.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   The logger service.
   * @param \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface $mimeTypeGuesser
   *   The mime type guesser service.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service.
   *
   * @return bool|\Drupal\htmlmail\Utility\HTMLMailMime
   *   FALSE if an error occured; otherwise a new MailMIME object containing
   *   the parsed message and its attachments, if any.
   */
  public static function &parse(
    $message,
    LoggerChannelFactoryInterface $logger,
    MimeTypeGuesserInterface $mimeTypeGuesser,
    FileSystemInterface $fileSystem) {

    $decoder = new \Mail_mimeDecode($message);
    $decoded = $decoder->decode(
      [
        'decode_headers' => TRUE,
        'decode_bodies' => TRUE,
        'include_bodies' => TRUE,
        'rfc822_bodies' => TRUE,
      ]
    );
    if (!self::successful($decoded)) {
      return FALSE;
    }
    $parsed = new HTMLMailMime($logger, $mimeTypeGuesser, $fileSystem);
    self::parseDecoded($parsed, $decoded);
    return $parsed;
  }

  /**
   * Return a (headers, body) pair for sending.
   *
   * Merge the $headers parameter with the MIME headers
   * and return it with the fully-encoded message body.
   *
   * @param array $headers
   *   The original message headers array.
   *
   * @return array
   *   An array containing two elements, the merged headers and the fully-
   *   encoded message body, both ready to send.
   */
  public function toEmail(array $headers) {
    $headers = self::toHeaders($headers);
    $mime_headers = $this->headers();
    return [
      array_diff_key($headers, $mime_headers) + $mime_headers,
      $this->get([], NULL, TRUE),
    ];
  }

  /**
   * Recursively copies message parts into a MailMIME object.
   *
   * Copies the MIME parts from an object returned by Mail_mimeDecode->decode()
   * into a MailMIME object, including subparts of any 'multipart' parts.
   *
   * @param \Drupal\htmlmail\Utility\HTMLMailMime $parsed
   *   The target MailMIME object.
   * @param object $decoded
   *   The object returned by Mail_mimeDecode->decode() whose MIME parts
   *   are being copied.
   * @param string $parent_subtype
   *   The content-type subtype of the parent multipart MIME part.  This should
   *   be either 'mixed', 'related', or 'alternative'.  Defaults to an empty
   *   string, signifying the root of the MIME tree.
   */
  protected static function parseDecoded(
    HTMLMailMime &$parsed,
    &$decoded,
    $parent_subtype = ''
  ) {
    if ($decoded->ctype_primary == 'multipart') {
      if (!empty($decoded->parts)) {
        foreach (array_keys($decoded->parts) as $key) {
          self::parseDecoded($parsed, $decoded->parts[$key], $decoded->ctype_secondary);
        }
      }
      return;
    }
    if (empty($decoded->body)) {
      return;
    }
    switch ($decoded->ctype_primary) {
      case 'text':
        if ($parent_subtype == ''
          || $parent_subtype == 'alternative'
          || $parent_subtype == 'related') {
          if ($decoded->ctype_secondary == 'plain') {
            $parsed->setTxtBody($decoded->body);
            return;
          }
          elseif ($decoded->ctype_secondary == 'html') {
            $parsed->setHtmlBody($decoded->body);
            return;
          }
        }
        break;

      case 'image':
        if ($parent_subtype == 'related') {
          $cid = isset($decoded->headers['content-id'])
            ? $decoded->headers['content-id'] : NULL;
          return;
        }
        break;

      default:
        $type = $decoded->ctype_primary . '/' . $decoded->ctype_secondary;
        $name = isset($decoded->d_parameters['name'])
          ? $decoded->d_parameters['name'] :
          (isset($decoded->d_parameters['filename'])
            ? $decoded->d_parameters['filename']
            : ''
          );
        if (!empty($name) && !empty($cid)) {
          $parsed->addHtmlImage($decoded->body, $type, $name, FALSE, $cid);
          return;
        }
        $parsed->addAttachment($decoded->body, $type, $name, FALSE);
    }
  }

  /**
   * Returns an array with keys changed to match the case of email headers.
   *
   * @param string|array $input
   *   The headers to be changed, either as a MAIL_MIME_CRLF-delimited string
   *   or as an associative array of (name => value) pairs.
   *
   * @return array
   *   An associative array of (name => value) pairs, with the case changed to
   *   match normal email headers.
   */
  public static function toHeaders($input) {
    $headers = [];
    if (!is_array($input)) {
      $decoder = new \Mail_mimeDecode($input);
      $input = $decoder->decode([
        'decode_headers' => TRUE,
        'input' => $input,
      ]);
    }
    foreach ($input as $name => $value) {
      $name = preg_replace(
        [
          '/([[:alpha:]])([[:alpha:]]+)/',
          '/^Mime-/',
          '/-Id$/',
        ],
        [
          'strtoupper("\1") . strtolower("\2")',
          'MIME-',
          '-ID',
        ],
        $name
      );
      $headers[$name] = $value;
    }
    return $headers;
  }

  /**
   * Collapses a message array into a single string.
   *
   * Also, standardizes the line-ending character.
   *
   * @param array|string $data
   *   The original message array or string.
   * @param string $eol
   *   The end of line characters.
   *
   * @return string
   *   The collapsed message string.
   */
  public static function concat($data, $eol) {
    $data = preg_replace('/(\r|\r\n|\n)/', $eol, $data);
    if (is_array($data)) {
      $data = implode($eol, $data);
    }
    return $data;
  }

  /**
   * Convert message headers and body into an encoded string.
   *
   * @param array $headers
   *   The message headers as a string or an array.
   * @param string|array $body
   *   The message body as a string or an array.
   * @param string $eol
   *   The end of line characters.
   *
   * @return string
   *   The fully-encoded email message as a string.
   */
  public static function encodeEmail(array $headers, $body, $eol) {
    // Standardize capitalization of header names.
    $headers = self::toHeaders($headers);
    $output = '';
    foreach ($headers as $name => $value) {
      $output .= $name . ': ' . \Mail_mimePart::encodeHeader(
          $name, $value, 'UTF-8', 'quoted-printable', $eol
        ) . $eol;
    }
    $output .= $eol . self::concat($body, $eol);
    return $output;
  }

  /**
   * Get the text version of the headers, which can be used in the mail.
   *
   * @param array $extra_headers
   *   (optional) An associative array of extra headers to add.  The format is
   *   array('Header-Name' => 'Header-Value').  Don't set the Content-Type for
   *   multipart messages here!
   * @param bool $overwrite
   *   (optional) TRUE if $extra_headers should overwrite existing data.
   *   Defaults to FALSE.
   * @param bool $skip_content
   *   (optional) TRUE if the following headers should not be returned:
   *   - Content-Type:
   *   - Content-Disposition:
   *   - Content-Transfer-Encoding:
   *   Defaults to FALSE.
   *
   * @return string
   *   The headers as a string.
   */
  public function mimeTxtHeaders(array $extra_headers = NULL, $overwrite = FALSE, $skip_content = FALSE) {
    return parent::txtHeaders($extra_headers, $overwrite, $skip_content);
  }

  /**
   * Returns the text/html message part.
   *
   * @return string|null
   *   The text/html message part, or NULL if it has not been set.
   */
  public function getHtmlBody() {
    return parent::getHTMLBody();
  }

  /**
   * Returns the text/plain message part.
   *
   * @return string|null
   *   The text/plain message part, or NULL if it has not been set.
   */
  public function getTxtBody() {
    return parent::getTXTBody();
  }

}
