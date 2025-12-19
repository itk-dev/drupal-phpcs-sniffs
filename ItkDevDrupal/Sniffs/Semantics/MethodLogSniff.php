<?php

namespace ItkDevDrupal\Sniffs\Semantics;

use Drupal\Sniffs\Semantics\FunctionTSniff;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Check the usage of the log() method calls.
 *
 * Methods from LoggerTrait are also checked.
 *
 * @see https://github.com/php-fig/log/blob/master/src/LoggerTrait.php
 */
class MethodLogSniff extends FunctionTSniff {

  /**
   * {@inheritdoc}
   */
  public function registerFunctionNames() {
    return [
      'log',
      'alert',
      'critical',
      'debug',
      'emergency',
      'error',
      'info',
      'notice',
      'warning',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function processFunctionCall(
    File $phpcsFile,
    $stackPtr,
    $openBracket,
    $closeBracket,
  ) {
    $tokens = $phpcsFile->getTokens();
    $name = $tokens[$stackPtr]['content'] ?? NULL;
    $argument = $this->getArgument(1);

    // Lifted from parent::processFunctionCall with `t()` replaced with `$name()`.
    if ($argument === FALSE) {
      $error = "Empty calls to $name() are not allowed";
      $phpcsFile->addError($error, $stackPtr, 'EmptyLog');
      return;
    }

    if ($tokens[$argument['start']]['code'] !== T_CONSTANT_ENCAPSED_STRING) {
      // Not a translatable string literal.
      $warning = "Only string literals should be passed to $name()";
      $phpcsFile->addError($warning, $argument['start'], 'NotLiteralString');
      return;
    }

    $string = $tokens[$argument['start']]['content'];
    if ($string === '""' || $string === "''") {
      $warning = "Do not pass empty strings to $name()";
      $phpcsFile->addError($warning, $argument['start'], 'EmptyString');
      return;
    }

    $concatAfter = $phpcsFile->findNext(Tokens::$emptyTokens, ($closeBracket + 1), NULL, TRUE, NULL, TRUE);
    if ($concatAfter !== FALSE && $tokens[$concatAfter]['code'] === T_STRING_CONCAT) {
      $stringAfter = $phpcsFile->findNext(Tokens::$emptyTokens, ($concatAfter + 1), NULL, TRUE, NULL, TRUE);
      if ($stringAfter !== FALSE
        && $tokens[$stringAfter]['code'] === T_CONSTANT_ENCAPSED_STRING
        && $this->checkConcatString($tokens[$stringAfter]['content']) === FALSE
      ) {
        $warning = "Do not concatenate strings to translatable strings, they should be part of the $name() argument and you should use placeholders";
        $phpcsFile->addWarning($warning, $stringAfter, 'ConcatString');
      }
    }

    $lastChar = substr($string, -1);
    if ($lastChar === '"' || $lastChar === "'") {
      $message = substr($string, 1, -1);
      if ($message !== trim($message)) {
        $warning = "Translatable strings must not begin or end with white spaces, use placeholders with $name() for variables";
        $phpcsFile->addWarning($warning, $argument['start'], 'WhiteSpace');
      }
    }

    $concatFound = $phpcsFile->findNext(T_STRING_CONCAT, $argument['start'], $argument['end']);
    if ($concatFound !== FALSE) {
      $error = 'Concatenating translatable strings is not allowed, use placeholders instead and only one string literal';
      $phpcsFile->addError($error, $concatFound, 'Concat');
    }

    // Check if there is a backslash escaped single quote in the string and
    // if the string makes use of double quotes.
    if ($string[0] === "'" && strpos($string, "\'") !== FALSE
      && strpos($string, '"') === FALSE
    ) {
      $warn = 'Avoid backslash escaping in translatable strings when possible, use "" quotes instead';
      $phpcsFile->addWarning($warn, $argument['start'], 'BackslashSingleQuote');
      return;
    }

    if ($string[0] === '"' && strpos($string, '\"') !== FALSE
      && strpos($string, "'") === FALSE
    ) {
      $warn = "Avoid backslash escaping in translatable strings when possible, use '' quotes instead";
      $phpcsFile->addWarning($warn, $argument['start'], 'BackslashDoubleQuote');
    }

  }

}
