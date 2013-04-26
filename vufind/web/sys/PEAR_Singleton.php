<?php
require_once 'PEAR.php';
/**
 * A singleton wrapper for PEAR to more easily update incorrect code references
 *
 * @category VuFind-Plus 
 * @author Mark Noble <mark@marmot.org>
 * Date: 4/25/13
 * Time: 4:22 PM
 */
class PEAR_Singleton {

	/** @var  PEAR $pear */
	private static $pear;
	public static function init(){
		PEAR_Singleton::$pear = new PEAR();
	}

	/**
	 * This method is a wrapper that returns an instance of the
	 * configured error class with this object's default error
	 * handling applied.  If the $mode and $options parameters are not
	 * specified, the object's defaults are used.
	 *
	 * @param mixed $message a text error message or a PEAR error object
	 *
	 * @param int $code      a numeric error code (it is up to your class
	 *                  to define these if you want to use codes)
	 *
	 * @param int $mode      One of PEAR_ERROR_RETURN, PEAR_ERROR_PRINT,
	 *                  PEAR_ERROR_TRIGGER, PEAR_ERROR_DIE,
	 *                  PEAR_ERROR_CALLBACK, PEAR_ERROR_EXCEPTION.
	 *
	 * @param mixed $options If $mode is PEAR_ERROR_TRIGGER, this parameter
	 *                  specifies the PHP-internal error level (one of
	 *                  E_USER_NOTICE, E_USER_WARNING or E_USER_ERROR).
	 *                  If $mode is PEAR_ERROR_CALLBACK, this
	 *                  parameter specifies the callback function or
	 *                  method.  In other error modes this parameter
	 *                  is ignored.
	 *
	 * @param string $userinfo If you need to pass along for example debug
	 *                  information, this parameter is meant for that.
	 *
	 * @param string $error_class The returned error object will be
	 *                  instantiated from this class, if specified.
	 *
	 * @param bool $skipmsg If true, raiseError will only pass error codes,
	 *                  the error message parameter will be dropped.
	 *
	 * @access public
	 * @return object   a PEAR error object
	 * @see PEAR::setErrorHandling
	 * @since PHP 4.0.5
	 */
	static function &raiseError($message = null,
	                     $code = null,
	                     $mode = null,
	                     $options = null,
	                     $userinfo = null,
	                     $error_class = null,
	                     $skipmsg = false)
	{
		return PEAR_Singleton::$pear->raiseError($message, $code, $mode, $options, $userinfo, $error_class, $skipmsg);
	}

	/**
	 * Sets how errors generated by this object should be handled.
	 * Can be invoked both in objects and statically.  If called
	 * statically, setErrorHandling sets the default behaviour for all
	 * PEAR objects.  If called in an object, setErrorHandling sets
	 * the default behaviour for that object.
	 *
	 * @param int $mode
	 *        One of PEAR_ERROR_RETURN, PEAR_ERROR_PRINT,
	 *        PEAR_ERROR_TRIGGER, PEAR_ERROR_DIE,
	 *        PEAR_ERROR_CALLBACK or PEAR_ERROR_EXCEPTION.
	 *
	 * @param mixed $options
	 *        When $mode is PEAR_ERROR_TRIGGER, this is the error level (one
	 *        of E_USER_NOTICE, E_USER_WARNING or E_USER_ERROR).
	 *
	 *        When $mode is PEAR_ERROR_CALLBACK, this parameter is expected
	 *        to be the callback function or method.  A callback
	 *        function is a string with the name of the function, a
	 *        callback method is an array of two elements: the element
	 *        at index 0 is the object, and the element at index 1 is
	 *        the name of the method to call in the object.
	 *
	 *        When $mode is PEAR_ERROR_PRINT or PEAR_ERROR_DIE, this is
	 *        a printf format string used when printing the error
	 *        message.
	 *
	 * @access public
	 * @return void
	 * @see PEAR_ERROR_RETURN
	 * @see PEAR_ERROR_PRINT
	 * @see PEAR_ERROR_TRIGGER
	 * @see PEAR_ERROR_DIE
	 * @see PEAR_ERROR_CALLBACK
	 * @see PEAR_ERROR_EXCEPTION
	 *
	 * @since PHP 4.0.5
	 */

	static function setErrorHandling($mode = null, $options = null)
	{
		PEAR_Singleton::$pear->setErrorHandling($mode, $options);
	}

	/**
	 * Tell whether a value is a PEAR error.
	 *
	 * @param   mixed $data   the value to test
	 * @param   int   $code   if $data is an error object, return true
	 *                        only if $code is a string and
	 *                        $obj->getMessage() == $code or
	 *                        $code is an integer and $obj->getCode() == $code
	 * @access  public
	 * @return  bool    true if parameter is an error
	 */
	static function isError($data, $code = null)
	{
		return PEAR_Singleton::$pear->isError($data, $code);
	}

	/**
	 * If you have a class that's mostly/entirely static, and you need static
	 * properties, you can use this method to simulate them. Eg. in your method(s)
	 * do this: $myVar = &PEAR5::getStaticProperty('myclass', 'myVar');
	 * You MUST use a reference, or they will not persist!
	 *
	 * @access public
	 * @param  string $class  The calling classname, to prevent clashes
	 * @param  string $var    The variable to retrieve.
	 * @return mixed   A reference to the variable. If not set it will be
	 *                 auto initialised to NULL.
	 */
	static function &getStaticProperty($class, $var)
	{
		return PEAR_Singleton::$pear->getStaticProperty($class, $var);
	}
}