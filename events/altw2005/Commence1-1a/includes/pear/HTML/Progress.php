<?php
// +----------------------------------------------------------------------+
// | PEAR :: HTML :: Progress                                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 1997-2004 The PHP Group                                |
// +----------------------------------------------------------------------+
// | This source file is subject to version 3.0 of the PHP license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available at through the world-wide-web at                           |
// | http://www.php.net/license/3_0.txt.                                  |
// | If you did not receive a copy of the PHP license and are unable to   |
// | obtain it through the world-wide-web, please send a note to          |
// | license@php.net so we can mail you a copy immediately.               |
// +----------------------------------------------------------------------+
// | Author: Laurent Laville <pear@laurent-laville.org>                   |
// +----------------------------------------------------------------------+
//
// $Id: Progress.php,v 1.1 2004/08/23 14:19:24 tjdet Exp $

/**
 * The HTML_Progress class allow you to add a loading bar
 * to any of your xhtml document.
 * You should have a browser that accept DHTML feature.
 *
 * @version    1.2.0
 * @author     Laurent Laville <pear@laurent-laville.org>
 * @access     public
 * @category   HTML
 * @package    HTML_Progress
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @tutorial   HTML_Progress.pkg
 */

require_once 'PEAR/ErrorStack.php';
require_once 'Log.php';
require_once 'HTML/Progress/DM.php';
require_once 'HTML/Progress/UI.php';

/**#@+
 * Progress Bar shape types
 *
 * @var        integer
 * @since      0.6
 */
define ('HTML_PROGRESS_BAR_HORIZONTAL', 1);
define ('HTML_PROGRESS_BAR_VERTICAL',   2);
/**#@-*/

/**#@+
 * Progress Bar shape types
 *
 * @var        integer
 * @since      1.2.0RC1
 */
define ('HTML_PROGRESS_POLYGONAL',      3);
define ('HTML_PROGRESS_CIRCLE',         4);
/**#@-*/

/**
 * Basic error code that indicate a wrong input
 *
 * @var        integer
 * @since      1.0
 */
define ('HTML_PROGRESS_ERROR_INVALID_INPUT',   -100);

/**
 * Basic error code that indicate a wrong callback definition.
 * Allows only function or class-method structure. 
 *
 * @var        integer
 * @since      1.1
 */
define ('HTML_PROGRESS_ERROR_INVALID_CALLBACK',-101);

/**
 * Basic error code that indicate a deprecated method
 * that may be removed at any time from a future version
 *
 * @var        integer
 * @since      1.2.0RC1
 */
define ('HTML_PROGRESS_DEPRECATED',            -102);


class HTML_Progress
{
    /**
     * Whether the progress bar is in determinate or indeterminate mode.
     * The default is false.
     * An indeterminate progress bar continuously displays animation indicating
     * that an operation of unknown length is occuring.
     *
     * @var        boolean
     * @since      1.0
     * @access     private
     * @see        setIndeterminate(), isIndeterminate()
     */
    var $_indeterminate;

    /**
     * Whether to display a border around the progress bar.
     * The default is false.
     *
     * @var        boolean
     * @since      1.0
     * @access     private
     * @see        setBorderPainted(), isBorderPainted()
     */
    var $_paintBorder;

    /**
     * Whether to textually display a string on the progress bar.
     * The default is false.
     * Setting this to true causes a textual display of the progress to be rendered 
     * on the progress bar. If the $_progressString is null, the percentage of completion
     * is displayed on the progress bar. Otherwise, the $_progressString is rendered
     * on the progress bar.
     *
     * @var        boolean
     * @since      1.0
     * @access     private
     * @see        setStringPainted(), isStringPainted()
     */
    var $_paintString;

    /**
     * An optional string that can be displayed on the progress bar.
     * The default is null.
     * Setting this to a non-null value does not imply that the string 
     * will be displayed.
     *
     * @var        string
     * @since      1.0
     * @access     private
     * @see        getString(), setString()
     */
    var $_progressString;

    /**
     * The data model (HTML_Progress_DM instance or extends) 
     * handles any mathematical issues arising from assigning faulty values.
     *
     * @var        object
     * @since      1.0
     * @access     private
     * @see        getDM(), setDM()
     */
    var $_DM;

    /**
     * The user interface (HTML_Progress_UI instance or extends)
     * handles look-and-feel of the progress bar.
     *
     * @var        object
     * @since      1.0
     * @access     private
     * @see        getUI(), setUI()
     */
    var $_UI;

    /**
     * The label that uniquely identifies this progress object.
     *
     * @var        string
     * @since      1.0
     * @access     private
     * @see        getIdent(), setIdent()
     */
    var $_ident;

    /**
     * Holds all HTML_Progress_Observer objects that wish to be notified of new messages.
     *
     * @var        array
     * @since      1.0
     * @access     private
     * @see        getListeners(), addListener(), removeListener()
     */
    var $_listeners;

    /**
     * Package name used by PEAR_ErrorStack functions
     *
     * @var        string
     * @since      1.0
     * @access     private
     */
    var $_package;

    /**
     * Delay in milisecond before each progress cells display.
     * 1000 ms === sleep(1)
     * <strong>usleep()</strong> function does not run on Windows platform.
     *
     * @var        integer
     * @since      1.1
     * @access     private
     * @see        setAnimSpeed()
     */
    var $_anim_speed;

    /**
     * Callback, either function name or array(&$object, 'method')
     *
     * @var        mixed
     * @since      1.2.0RC3
     * @access     private
     * @see        setProgressHandler()
     */
    var $_callback = null;

    
    /**
     * Constructor Summary
     *
     * o Creates a natural horizontal progress bar that displays ten cells/units
     *   with no border and no progress string.
     *   The initial and minimum values are 0, and the maximum is 100.
     *   <code>
     *   $bar = new HTML_Progress();
     *   </code>
     *
     * o Creates a natural progress bar with the specified orientation, which can be
     *   either HTML_PROGRESS_BAR_HORIZONTAL or HTML_PROGRESS_BAR_VERTICAL
     *   By default, no border and no progress string are painted.
     *   The initial and minimum values are 0, and the maximum is 100.
     *   <code>
     *   $bar = new HTML_Progress($orient);
     *   </code>
     *
     * o Creates a natural horizontal progress bar with the specified minimum and
     *   maximum. Sets the initial value of the progress bar to the specified
     *   minimum, and the maximum that the progress bar can reach.
     *   By default, no border and no progress string are painted.
     *   <code>
     *   $bar = new HTML_Progress($min, $max);
     *   </code>
     *
     * o Creates a natural horizontal progress bar with the specified orientation, 
     *   minimum and maximum. Sets the initial value of the progress bar to the 
     *   specified minimum, and the maximum that the progress bar can reach.
     *   By default, no border and no progress string are painted.
     *   <code>
     *   $bar = new HTML_Progress($orient, $min, $max);
     *   </code>
     *
     * o Creates a natural horizontal progress that uses the specified model
     *   to hold the progress bar's data.
     *   By default, no border and no progress string are painted.
     *   <code>
     *   $bar = new HTML_Progress($model);
     *   </code>
     *
     *
     * @param      object    $model         (optional) Model that hold the progress bar's data
     * @param      int       $orient        (optional) Orientation of progress bar
     * @param      int       $min           (optional) Minimum value of progress bar
     * @param      int       $max           (optional) Maximum value of progress bar
     * @param      array     $errorPrefs    (optional) Always last argument of class constructor.
     *                                       hash of params to configure PEAR_ErrorStack and loggers
     *
     * @since      1.0
     * @access     public
     * @throws     HTML_PROGRESS_ERROR_INVALID_INPUT
     * @see        setIndeterminate(), 
     *             setBorderPainted(), setStringPainted(), setString(),
     *             setDM(), setUI(), setIdent()
     */
    function HTML_Progress() 
    {
        $args = func_get_args();
        $num_args = func_num_args();

        if ($num_args > 0) {
            $errorPrefs = func_get_arg($num_args - 1);
            if (!is_array($errorPrefs)) {
                $errorPrefs = array();
            } else {
                $num_args--;
            }
            $this->_initErrorStack($errorPrefs);
        } else {        	
            $this->_initErrorStack();
        }

        $this->_listeners = array();          // none listeners by default

        $this->_DM = new HTML_Progress_DM();  // new instance of a progress DataModel
        $this->_UI = new HTML_Progress_UI();  // new instance of a progress UserInterface

        switch ($num_args) {
         case 1:
            if (is_object($args[0]) && (is_a($args[0], 'html_progress_dm'))) {
                /*   object html_progress_dm extends   */
                $this->_DM = &$args[0];
                
            } elseif (is_int($args[0])) {
                /*   int orient   */
                $this->_UI->setOrientation($args[0]);

            } else {
                return $this->raiseError(HTML_PROGRESS_ERROR_INVALID_INPUT, 'exception',
                    array('var' => '$model | $orient',
                          'was' => (gettype($args[0]) == 'object') ? 
                                    get_class($args[0]).' object' : gettype($args[0]),
                          'expected' => 'html_progress_dm object | integer',
                          'paramnum' => 1));
            }
            break;
         case 2:
            /*   int min, int max   */
            if (!is_int($args[0])) {
                return $this->raiseError(HTML_PROGRESS_ERROR_INVALID_INPUT, 'exception',
                    array('var' => '$min',
                          'was' => $args[0],
                          'expected' => 'integer',
                          'paramnum' => 1));

            } elseif (!is_int($args[1])) {
                return $this->raiseError(HTML_PROGRESS_ERROR_INVALID_INPUT, 'exception',
                    array('var' => '$max',
                          'was' => $args[1],
                          'expected' => 'integer',
                          'paramnum' => 2));
            } else {
                $this->_DM->setMinimum($args[0]);
                $this->_DM->setMaximum($args[1]);
            } 
            break;
         case 3:
            /*   int orient, int min, int max   */
            if (!is_int($args[0])) {
                return $this->raiseError(HTML_PROGRESS_ERROR_INVALID_INPUT, 'exception',
                    array('var' => '$orient',
                          'was' => $args[0],
                          'expected' => 'integer',
                          'paramnum' => 1));

            } elseif (!is_int($args[1])) {
                return $this->raiseError(HTML_PROGRESS_ERROR_INVALID_INPUT, 'exception',
                    array('var' => '$min',
                          'was' => $args[1],
                          'expected' => 'integer',
                          'paramnum' => 2));

            } elseif (!is_int($args[2])) {
                return $this->raiseError(HTML_PROGRESS_ERROR_INVALID_INPUT, 'exception',
                    array('var' => '$max',
                          'was' => $args[2],
                          'expected' => 'integer',
                          'paramnum' => 3));
            } else {
                $this->_UI->setOrientation($args[0]);
                $this->_DM->setMinimum($args[1]);
                $this->_DM->setMaximum($args[2]);
            }
            break;
         default:
        }
        $this->setString(null);
        $this->setStringPainted(false);
        $this->setBorderPainted(false);
        $this->setIndeterminate(false);
        $this->setIdent();
        $this->setAnimSpeed(0);
        
        // to fix a potential php config problem with PHP 4.2.0 : turn 'implicit_flush' ON
        ob_implicit_flush(1);
    }

    /**
     * Returns the current API version
     *
     * @return     float
     * @since      0.1
     * @access     public
     */
    function apiVersion()
    {
        return 1.2;
    }

    /**
     * Returns mode of the progress bar (determinate or not).
     *
     * @return     boolean
     * @since      1.0
     * @access     public
     * @see        setIndeterminate()
     * @tutorial   progress.isindeterminate.pkg
     */
    function isIndeterminate()
    {
        return $this->_indeterminate;
    }

    /**
     * Sets the $_indeterminate property of the progress bar, which determines
     * whether the progress bar is in determinate or indeterminate mode.
     * An indeterminate progress bar continuously displays animation indicating
     * that an operation of unknown length is occuring.
     * By default, this property is false.
     *
     * @param      boolean   $continuous    whether countinuously displays animation
     *
     * @return     void
     * @since      1.0
     * @access     public
     * @throws     HTML_PROGRESS_ERROR_INVALID_INPUT
     * @see        isIndeterminate()
     * @tutorial   progress.setindeterminate.pkg
     */
    function setIndeterminate($continuous)
    {
        if (!is_bool($continuous)) {
            return $this->raiseError(HTML_PROGRESS_ERROR_INVALID_INPUT, 'exception',
                array('var' => '$continuous',
                      'was' => gettype($continuous),
                      'expected' => 'boolean',
                      'paramnum' => 1));
        }
        $this->_indeterminate = $continuous;
    }

    /**
     * Determines whether the progress bar border is painted or not.
     * The default is false.
     *
     * @return     boolean
     * @since      1.0
     * @access     public
     * @see        setBorderPainted()
     * @tutorial   progress.isborderpainted.pkg
     */
    function isBorderPainted()
    {
        return $this->_paintBorder;
    }

    /**
     * Sets the value of $_paintBorder property, which determines whether the
     * progress bar should paint its border. The default is false. 
     *
     * @param      boolean   $paint         whether the progress bar should paint its border
     *
     * @return     void
     * @since      1.0
     * @access     public
     * @throws     HTML_PROGRESS_ERROR_INVALID_INPUT
     * @see        isBorderPainted()
     * @tutorial   progress.setborderpainted.pkg
     */
    function setBorderPainted($paint)
    {
        if (!is_bool($paint)) {
            return $this->raiseError(HTML_PROGRESS_ERROR_INVALID_INPUT, 'exception',
                array('var' => '$paint',
                      'was' => gettype($paint),
                      'expected' => 'boolean',
                      'paramnum' => 1));
        }

        $this->_paintBorder = $paint;
    }

    /**
     * Determines whether the progress bar string is painted or not. 
     * The default is false.
     * The progress bar displays the value returned by getPercentComplete() method
     * formatted as a percent such as 33%.
     *
     * @return     boolean
     * @since      1.0
     * @access     public
     * @see        setStringPainted(), setString()
     * @tutorial   progress.isstringpainted.pkg
     */
    function isStringPainted()
    {
        return $this->_paintString;
    }

    /**
     * Sets the value of $_paintString property, which determines whether the
     * progress bar should render a progress string. The default is false.
     *
     * @param      boolean   $paint         whether the progress bar should render a string
     *
     * @return     void
     * @since      1.0
     * @access     public
     * @throws     HTML_PROGRESS_ERROR_INVALID_INPUT
     * @see        isStringPainted(), setString()
     * @tutorial   progress.setstringpainted.pkg
     */
    function setStringPainted($paint)
    {
        if (!is_bool($paint)) {
            return $this->raiseError(HTML_PROGRESS_ERROR_INVALID_INPUT, 'exception',
                array('var' => '$paint',
                      'was' => gettype($paint),
                      'expected' => 'boolean',
                      'paramnum' => 1));
        }
        $this->_paintString = $paint;
    }

    /**
     * Returns the current value of the progress string.
     * By default, the progress bar displays the value returned by 
     * getPercentComplete() method formatted as a percent such as 33%.
     *
     * @return     string
     * @since      1.0
     * @access     public
     * @see        setString(), isStringPainted()
     * @tutorial   progress.getstring.pkg
     */
    function getString()
    {
        if ($this->isStringPainted() && !is_null($this->_progressString)) {
            return $this->_progressString;
        } else {
            return sprintf("%s", $this->getPercentComplete()*100).' %';
	}
    }

    /**
     * Sets the current value of the progress string. By default, this string
     * is null. If you have provided a custom progress string and want to revert
     * to the built-in-behavior, set the string back to null.
     * The progress string is painted only if the isStringPainted() method
     * returns true.
     *
     * @param      string    $str           progress string
     *
     * @return     void
     * @since      1.0
     * @access     public
     * @see        getString(), isStringPainted(), setStringPainted()
     * @tutorial   progress.setstring.pkg
     */
    function setString($str)
    {
        $this->_progressString = $str;
    }

    /**
     * Returns the data model used by this progress bar.
     *
     * @return     object
     * @since      1.0
     * @access     public
     * @see        setDM()
     */
    function &getDM()
    {
        return $this->_DM;
    }

    /**
     * Sets the data model used by this progress bar.
     *
     * @param      string    $model         class name of a html_progress_dm extends object
     *
     * @return     void
     * @since      1.0
     * @access     public
     * @throws     HTML_PROGRESS_ERROR_INVALID_INPUT
     * @see        getDM()
     */
    function setDM($model)
    {
        if (!class_exists($model)) {
            return $this->raiseError(HTML_PROGRESS_ERROR_INVALID_INPUT, 'error',
                array('var' => '$model',
                      'was' => 'class does not exists',
                      'expected' => $model.' class defined',
                      'paramnum' => 1));
        }

        $_dm = new $model();

        if (!is_a($_dm, 'html_progress_dm')) {
            return $this->raiseError(HTML_PROGRESS_ERROR_INVALID_INPUT, 'error',
                array('var' => '$model',
                      'was' => $model,
                      'expected' => 'HTML_Progress_DM extends',
                      'paramnum' => 1));
        }
        $this->_DM =& $_dm;
    }

    /**
     * Returns the progress bar's minimum value stored in the progress bar's data model.
     * The default value is 0.
     *
     * @return     integer
     * @since      1.0
     * @access     public
     * @see        setMinimum(),
     *             HTML_Progress_DM::getMinimum()
     * @tutorial   dm.getminimum.pkg
     */
    function getMinimum()
    {
        return $this->_DM->getMinimum();
    }

    /**
     * Sets the progress bar's minimum value stored in the progress bar's data model.
     * If the minimum value is different from previous minimum, all change listeners
     * are notified.
     *
     * @param      integer   $min           progress bar's minimal value
     *
     * @return     void
     * @since      1.0
     * @access     public
     * @see        getMinimum(),
     *             HTML_Progress_DM::setMinimum()
     * @tutorial   dm.setminimum.pkg
     */
    function setMinimum($min)
    {
        $oldVal = $this->getMinimum();

        $this->_DM->setMinimum($min);

        if ($oldVal != $min) {
            $this->_announce(array('log' => 'setMinimum', 'value' => $min));
        }
    }

    /**
     * Returns the progress bar's maximum value stored in the progress bar's data model.
     * The default value is 100.
     *
     * @return     integer
     * @since      1.0
     * @access     public
     * @see        setMaximum(),
     *             HTML_Progress_DM::getMaximum()
     * @tutorial   dm.getmaximum.pkg
     */
    function getMaximum()
    {
        return $this->_DM->getMaximum();
    }

    /**
     * Sets the progress bar's maximum value stored in the progress bar's data model.
     * If the maximum value is different from previous maximum, all change listeners
     * are notified.
     *
     * @param      integer   $max           progress bar's maximal value
     *
     * @return     void
     * @since      1.0
     * @access     public
     * @see        getMaximum(),
     *             HTML_Progress_DM::setMaximum()
     * @tutorial   dm.setmaximum.pkg
     */
    function setMaximum($max)
    {
        $oldVal = $this->getMaximum();

        $this->_DM->setMaximum($max);

        if ($oldVal != $max) {
            $this->_announce(array('log' => 'setMaximum', 'value' => $max));
        }
    }

    /**
     * Returns the progress bar's increment value stored in the progress bar's data model.
     * The default value is +1.
     *
     * @return     integer
     * @since      1.0
     * @access     public
     * @see        setIncrement(),
     *             HTML_Progress_DM::getIncrement()
     * @tutorial   dm.getincrement.pkg
     */
    function getIncrement()
    {
        return $this->_DM->getIncrement();
    }

    /**
     * Sets the progress bar's increment value stored in the progress bar's data model.
     *
     * @param      integer   $inc           progress bar's increment value
     *
     * @return     void
     * @since      1.0
     * @access     public
     * @see        getIncrement(),
     *             HTML_Progress_DM::setIncrement()
     * @tutorial   dm.setincrement.pkg
     */
    function setIncrement($inc)
    {
        $this->_DM->setIncrement($inc);
    }

    /**
     * Returns the progress bar's current value, which is stored in the 
     * progress bar's data model. The value is always between the minimum
     * and maximum values, inclusive.
     * By default, the value is initialized to be equal to the minimum value.
     *
     * @return     integer
     * @since      1.0
     * @access     public
     * @see        setValue(), incValue(),
     *             HTML_Progress_DM::getValue()
     * @tutorial   dm.getvalue.pkg
     */
    function getValue()
    {
        return $this->_DM->getValue();
    }

    /**
     * Sets the progress bar's current value stored in the progress bar's data model.
     * If the new value is different from previous value, all change listeners
     * are notified.
     *
     * @param      integer   $val           progress bar's current value
     *
     * @return     void
     * @since      1.0
     * @access     public
     * @see        getValue(), incValue(),
     *             HTML_Progress_DM::setValue()
     * @tutorial   dm.setvalue.pkg
     */
    function setValue($val)
    {
        $oldVal = $this->getValue();

        $this->_DM->setValue($val);

        if ($oldVal != $val) {
            $this->_announce(array('log' => 'setValue', 'value' => $val));
        }
    }

    /**
     * Updates the progress bar's current value by adding increment value.
     * All change listeners are notified.
     *
     * @return     void
     * @since      1.0
     * @access     public
     * @see        getValue(), setValue(),
     *             HTML_Progress_DM::incValue()
     * @tutorial   dm.incvalue.pkg
     */
    function incValue()
    {
        $this->_DM->incValue();
        $this->_announce(array('log' => 'incValue', 'value' => $this->_DM->getValue() ));
    }

    /**
     * Returns the percent complete for the progress bar. Note that this number is
     * between 0.00 and 1.00.
     *
     * @return     float
     * @since      1.0
     * @access     public
     * @see        getValue(), getMaximum(),
     *             HTML_Progress_DM::getPercentComplete()
     * @tutorial   dm.getpercentcomplete.pkg
     */
    function getPercentComplete()
    {
        return $this->_DM->getPercentComplete();
    }

    /**
     * Returns the look-and-feel object that renders the progress bar.
     *
     * @return     object
     * @since      1.0
     * @access     public
     * @see        setUI()
     */
    function &getUI()
    {
        return $this->_UI;
    }

    /**
     * Sets the look-and-feel object that renders the progress bar.
     *
     * @param      string    $ui            class name of a html_progress_ui extends object
     *
     * @return     void
     * @since      1.0
     * @access     public
     * @throws     HTML_PROGRESS_ERROR_INVALID_INPUT
     * @see        getUI()
     */
    function setUI($ui)
    {
        if (!class_exists($ui)) {
            return $this->raiseError(HTML_PROGRESS_ERROR_INVALID_INPUT, 'error',
                array('var' => '$ui',
                      'was' => 'class does not exists',
                      'expected' => $ui.' class defined',
                      'paramnum' => 1));
        }
        
        $_ui = new $ui();

        if (!is_a($_ui, 'html_progress_ui')) {
            return $this->raiseError(HTML_PROGRESS_ERROR_INVALID_INPUT, 'error',
                array('var' => '$ui',
                      'was' => $ui,
                      'expected' => 'HTML_Progress_UI extends',
                      'paramnum' => 1));
        }
        $this->_UI =& $_ui;
    }

    /**
     * Sets the look-and-feel model that renders the progress bar.
     *
     * @param      string    $file          file name of model properties
     * @param      string    $type          type of external ressource (phpArray, iniFile, XML ...)
     *
     * @return     void
     * @since      1.0
     * @access     public
     * @see        setUI()
     * @tutorial   progress.setmodel.pkg
     */
    function setModel($file, $type)
    {
        include_once ('HTML/Progress/model.php');

        $this->_UI = new HTML_Progress_Model($file, $type);
    }

    /**
     * Returns delay execution of the progress bar
     *
     * @return     integer
     * @since      1.2.0RC1
     * @access     public
     * @see        setAnimSpeed()
     * @tutorial   progress.getanimspeed.pkg
     */
    function getAnimSpeed()
    {
        return $this->_anim_speed;
    }

    /**
     * Set the delays progress bar execution for the given number of miliseconds.
     *
     * @param      integer   $delay         Delay in milisecond.
     *
     * @return     void
     * @since      1.1
     * @access     public
     * @throws     HTML_PROGRESS_ERROR_INVALID_INPUT
     * @see        getAnimSpeed()
     * @tutorial   progress.setanimspeed.pkg
     */
    function setAnimSpeed($delay)
    {
        if (!is_int($delay)) {
            return $this->raiseError(HTML_PROGRESS_ERROR_INVALID_INPUT, 'exception',
                array('var' => '$delay',
                      'was' => gettype($delay),
                      'expected' => 'integer',
                      'paramnum' => 1));

        } elseif ($delay < 0) {
            return $this->raiseError(HTML_PROGRESS_ERROR_INVALID_INPUT, 'error',
                array('var' => '$delay',
                      'was' => $delay,
                      'expected' => 'greater than zero',
                      'paramnum' => 1));

        } elseif ($delay > 1000) {
            return $this->raiseError(HTML_PROGRESS_ERROR_INVALID_INPUT, 'error',
                array('var' => '$delay',
                      'was' => $delay,
                      'expected' => 'less or equal 1000',
                      'paramnum' => 1));
        }
        $this->_anim_speed = $delay;
    }

    /**
     * Get the cascading style sheet to put inline on HTML document
     *
     * @return     string
     * @since      1.0
     * @access     public
     * @see        HTML_Progress_UI::getStyle()
     * @tutorial   ui.getstyle.pkg
     */
    function getStyle()
    {
        $ui = $this->getUI();
        $lnEnd = $ui->_getLineEnd();
        
        $css =& $ui->getStyle();
        $style = $lnEnd . $css->toString();
        $style = preg_replace("/".$lnEnd."\./", ".".$this->getIdent()." .", $style);

        return $style;
    }

    /**
     * Get the javascript code to manage progress bar.
     *
     * @return     string                   JavaScript URL or inline code to manage progress bar
     * @since      1.0
     * @access     public
     * @see        HTML_Progress_UI::getScript()
     * @tutorial   ui.getscript.pkg
     */
    function getScript()
    {
        $ui = $this->getUI();
        $js =& $ui->getScript();
        return $js;
    }

    /**
     * Returns the progress bar structure in an array.
     *
     * @return     array of progress bar properties
     * @since      1.0
     * @access     public
     */
    function toArray()
    {
        $ui =& $this->getUI();
        $dm =& $this->getDM(); 

        $_structure = array();       
        $_structure['id'] = $this->getIdent();
        $_structure['indeterminate'] = $this->isIndeterminate();
        $_structure['borderpainted'] = $this->isBorderPainted();
        $_structure['stringpainted'] = $this->isStringPainted();
        $_structure['string'] = $this->_progressString;
        $_structure['animspeed'] = $this->getAnimSpeed();
        $_structure['ui']['classID'] = get_class($ui);
        $_structure['ui']['orientation'] = $ui->getOrientation();
        $_structure['ui']['fillway'] = $ui->getFillWay();
        $_structure['ui']['cell'] = $ui->getCellAttributes();
        $_structure['ui']['cell']['count'] = $ui->getCellCount();
        $_structure['ui']['border'] = $ui->getBorderAttributes();
        $_structure['ui']['string'] = $ui->getStringAttributes();
        $_structure['ui']['progress'] = $ui->getProgressAttributes();
        $_structure['ui']['script'] = $ui->getScript();
        $_structure['dm']['classID'] = get_class($dm);
        $_structure['dm']['minimum'] = $dm->getMinimum();
        $_structure['dm']['maximum'] = $dm->getMaximum();
        $_structure['dm']['increment'] = $dm->getIncrement();
        $_structure['dm']['value'] = $dm->getValue();
        $_structure['dm']['percent'] = $dm->getPercentComplete();

        return $_structure;
    }

    /**
     * Returns the progress structure as HTML.
     *
     * @return     string                   HTML Progress bar
     * @since      0.2
     * @access     public
     */
    function toHtml()
    {
        $strHtml = '';
        $ui =& $this->_UI;
        $tabs = $ui->_getTabs();
        $tab = $ui->_getTab();
        $lnEnd = $ui->_getLineEnd();
        $comment = $ui->getComment();
        $orient = $ui->getOrientation();
        $progressAttr = $ui->getProgressAttributes();
        $borderAttr = $ui->getBorderAttributes();
        $stringAttr = $ui->getStringAttributes();
        $valign = strtolower($stringAttr['valign']);
        
        /**
         *  Adds a progress bar legend in html code is possible.
         *  See HTML_Common::setComment() method.
         */
        if (strlen($comment) > 0) {
            $strHtml .= $tabs . "<!-- $comment -->" . $lnEnd;
        }

        $strHtml .= $tabs . "<div id=\"".$this->getIdent()."_progress\" class=\"".$this->getIdent()."\">" . $lnEnd;
        $strHtml .= $tabs . "<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\">" . $lnEnd;
        $progressId = $this->getIdent().'_';

        /**
         *  Creates all cells of progress bar in order
         *  depending of the FillWay and Orientation. 
         */
        if ($orient == HTML_PROGRESS_BAR_HORIZONTAL) {
            $progressHtml = $this->_getProgressHbar_toHtml();
        }

        if ($orient == HTML_PROGRESS_BAR_VERTICAL) {
            $progressHtml = $this->_getProgressVbar_toHtml();
        }

        if ($orient == HTML_PROGRESS_POLYGONAL) {
            $progressHtml = $this->_getProgressPolygonal_toHtml();
        }

        if ($orient == HTML_PROGRESS_CIRCLE) {
            $cellAttr = $ui->getCellAttributes();
            if (!isset($cellAttr[0]['background-image']) || !file_exists($cellAttr[0]['background-image'])) {
                // creates default circle segments pictures :
                // 'c0.png'->0% 'c1.png'->10%, 'c2.png'->20%, ... 'c10.png'->100%
                $ui->drawCircleSegments();
            }
            $progressHtml = $this->_getProgressCircle_toHtml();
        }

        /**
         *  Progress Bar (2) alignment rules:
         *  - percent / messsage area (1)
         *
         *  +---------------------------------------+
         *  |         +t1---+                       |
         *  |         | (1) |                       |
         *  |         +-----+                       |
         *  | +t2---+ +-------------------+ +t3---+ |
         *  | | (1) | | | | | (2) | | | | | | (1) | |
         *  | +-----+ +-------------------+ +-----+ |
         *  |         +t4---+                       |
         *  |         | (1) |                       |
         *  |         +-----+                       |
         *  +---------------------------------------+
         */
        if (($valign == 'left') || ($valign == 'right')) {
            $tRows = 1;
            $tCols = 2;
            $ps = ($valign == 'left') ? 0 : 1;
        } else {
            $tRows = 2;
            $tCols = 1;
            $ps = ($valign == 'top')  ? 0 : 1;
        }

        for ($r = 0 ; $r < $tRows ; $r++) {
            $strHtml .= $tabs . "<tr>" . $lnEnd;
            for ($c = 0 ; $c < $tCols ; $c++) {
                if (($c == $ps) || ($r == $ps)) {
                    $id = $stringAttr['id'];
                    $strHtml .= $tabs . $tab . "<td class=\"$id\" id=\"$progressId$id\">" . $lnEnd;
                    $strHtml .= $tabs . $tab . $tab . $this->getString() . $lnEnd;
                    $ps = -1;
                } else {
                    $class = $progressAttr['class'];
                    $strHtml .= $tabs . $tab ."<td class=\"$class\">" . $lnEnd;
                    $strHtml .= $tabs . $tab . $tab . "<div class=\"".$borderAttr['class']."\">" . $lnEnd;
                    $strHtml .= $progressHtml;
                    $strHtml .= $tabs . $tab . $tab . "</div>" . $lnEnd;
                }
                $strHtml .= $tabs . $tab ."</td>" . $lnEnd;
            }
            $strHtml .= $tabs . "</tr>" . $lnEnd;
        }

        $strHtml .= $tabs . "</table>" . $lnEnd;
        $strHtml .= $tabs . "</div>" . $lnEnd;

        return $strHtml;
    }

    /**
     * Renders the new value of progress bar.
     *
     * @return     void
     * @since      0.2
     * @access     public
     */
    function display()
    {
        static $lnEnd;
        static $cellAmount;
        static $determinate;

        if(!isset($lnEnd)) {
            $ui =& $this->_UI;
            $lnEnd = $ui->_getLineEnd();
            $cellAmount = ($this->getMaximum() - $this->getMinimum()) / $ui->getCellCount();
        }

        if (function_exists('ob_get_clean')) {
            $bar  = ob_get_clean();      // use for PHP 4.3+
        } else {
            $bar  = ob_get_contents();   // use for PHP 4.2+
            ob_end_clean();
        }
        $bar .= $lnEnd;

        $progressId = $this->getIdent().'_';

        if ($this->isIndeterminate()) {
            if (isset($determinate)) {
                $determinate++;
                $progress = $determinate;
            } else {
                $progress = $determinate = 1;
            }
        } else {
            $progress = ($this->getValue() - $this->getMinimum()) / $cellAmount;
            $determinate = 0;
	}
        $bar .= '<script type="text/javascript">self.setprogress("'.$progressId.'",'.((int) $progress).',"'.$this->getString().'",'.$determinate.'); </script>';

        echo $bar;
        ob_start();
    }

    /**
     * Hides the progress bar.
     *
     * @return     void
     * @since      1.2.0RC3
     * @access     public
     */
    function hide()
    {
        $ui = $this->getUI();
        $lnEnd = $ui->_getLineEnd();
        $progressId = $this->getIdent().'_';

        if (function_exists('ob_get_clean')) {
            $bar  = ob_get_clean();      // use for PHP 4.3+
        } else {
            $bar  = ob_get_contents();   // use for PHP 4.2+
            ob_end_clean();
        }
        $bar .= $lnEnd;
        $bar .= '<script type="text/javascript">self.hideProgress("'.$progressId.'"); </script>';
        echo $bar;
    }

    /**
     * Default user callback when none are defined
     *
     * @return     void
     * @since      1.2.0RC3
     * @access     public
     * @see        getAnimSpeed(), setAnimSpeed(), process()
     */
    function sleep()
    {
        // sleep a bit ...
        for ($i=0; $i<($this->getAnimSpeed()*1000); $i++) { }
    }

    /**
     * Sets the user callback function that execute all actions pending progress
     *
     * @param      mixed     $handler       Name of function or a class-method.
     *
     * @return     void
     * @since      1.2.0RC3
     * @access     public
     * @throws     HTML_PROGRESS_ERROR_INVALID_CALLBACK
     * @see        process()
     */
    function setProgressHandler($handler)
    {
        if (!is_callable($handler)) {
            return $this->raiseError(HTML_PROGRESS_ERROR_INVALID_CALLBACK, 'warning',
                array('var' => '$handler',
                      'element' => 'valid Class-Method/Function',
                      'was' => 'element',
                      'paramnum' => 1));
        }
        $this->_callback = $handler;
    }

    /**
     * Performs the progress actions
     *
     * @return     void
     * @since      1.2.0RC3
     * @access     public
     * @see        sleep()
     */
    function process()
    {
        if (!$this->_callbackExists($this->_callback)) {
            // when there is no valid user callback then default is to sleep a bit ...
            $this->sleep();
        } else {
            call_user_func($this->_callback, $this->getValue(), &$this);
        }
    }

    /**
     * Runs the progress bar (both modes: indeterminate and determinate),
     * and execute all actions defined in user callback identified by 
     * method setProgressHandler.
     *
     * @return     void
     * @since      1.2.0RC3
     * @access     public
     * @see        process(), setProgressHandler()
     */
    function run() 
    {
        do {
            $this->display();
            $this->process();
            if ($this->getPercentComplete() == 1) {
                if ($this->isIndeterminate()) {
                    $this->setValue(0);
                } else {
                    return;
                }
            }
            $this->incValue();
        } while(1);
    }

    /**
     * Returns the current identification string.
     *
     * @return     string                   current Progress instance's identification string
     * @since      1.0
     * @access     public
     * @see        setIdent()
     */
    function getIdent()
    {
        return $this->_ident;
    }

    /**
     * Sets this Progress instance's identification string.
     *
     * @param      mixed     $ident         (optional) the new identification string.
     *
     * @since      1.0
     * @access     public
     * @see        getIdent()
     */
    function setIdent($ident = null)
    {
        if (is_null($ident)) {
            $this->_ident = 'p_' . substr(md5(microtime()), 0, 6);
        } else {
            $this->_ident = $ident;
	}
    }

    /**
     * Returns an array of all the listeners added to this progress bar.
     *
     * @return     array
     * @since      1.0
     * @access     public
     * @see        addListener(), removeListener()
     * @tutorial   progress.getlisteners.pkg
     */
    function getListeners()
    {
        return $this->_listeners;
    }

    /**
     * Adds a HTML_Progress_Observer instance to the list of observers 
     * that are listening for messages emitted by this HTML_Progress instance.
     *
     * @param      object    $observer      The HTML_Progress_Observer instance 
     *                                      to attach as a listener.
     *
     * @return     boolean                  True if the observer is successfully attached.
     * @since      1.0
     * @access     public
     * @see        getListeners(), removeListener()
     * @tutorial   progress.addlistener.pkg
     */
    function addListener($observer)
    {
        if (!is_a($observer, 'HTML_Progress_Observer') &&
            !is_a($observer, 'HTML_Progress_Monitor')) {
            return false;
        }
        $this->_listeners[$observer->_id] = &$observer;
        return true;
    }

    /**
     * Removes a HTML_Progress_Observer instance from the list of observers.
     *
     * @param      object    $observer      The HTML_Progress_Observer instance 
     *                                      to detach from the list of listeners.
     *
     * @return     boolean                  True if the observer is successfully detached.
     * @since      1.0
     * @access     public
     * @see        getListeners(), addListener()
     * @tutorial   progress.removelistener.pkg
     */
    function removeListener($observer)
    {
        if ((!is_a($observer, 'HTML_Progress_Observer') && 
             !is_a($observer, 'HTML_Progress_Monitor')
             ) || 
            (!isset($this->_listeners[$observer->_id]))  ) {

            return false;
        }
        unset($this->_listeners[$observer->_id]);
        return true;
    }

    /**
     * Notifies all listeners that have registered interest in $event message.
     *
     * @param      mixed     $event         A hash describing the progress event.
     *
     * @since      1.0
     * @access     private
     * @see        setMinimum(), setMaximum(), setValue(), incValue()
     */
    function _announce($event)
    {
        foreach ($this->_listeners as $id => $listener) {
            $this->_listeners[$id]->notify($event);
        }
    }

    /**
     * Returns a horizontal progress bar structure as HTML.
     *
     * @return     string                   Horizontal HTML Progress bar
     * @since      1.0
     * @access     private
     */
    function _getProgressHbar_toHtml()
    {
        $ui =& $this->_UI;
        $tabs = $ui->_getTabs();
        $tab = $ui->_getTab();
        $lnEnd = $ui->_getLineEnd();
        $way_natural = ($ui->getFillWay() == 'natural');
        $cellAttr = $ui->getCellAttributes();
        $cellCount = $ui->getCellCount();

        $progressId = $this->getIdent().'_';
        $progressHtml = "";

        if ($way_natural) {
            // inactive cells first
            $pos = $cellAttr['spacing'];
            for ($i=0; $i<$cellCount; $i++) {
                $progressHtml .= $tabs . $tab . $tab;
                $progressHtml .= "<div id=\"". $progressId . sprintf($cellAttr['id'],$i) ."I\""; 
                $progressHtml .= " class=\"".$cellAttr['class']."I\"";
                $progressHtml .= " style=\"position:absolute;top:".$cellAttr['spacing']."px;left:".$pos."px;\"";
                $progressHtml .= ">&nbsp;</div>" . $lnEnd;
                $pos += ($cellAttr['width'] + $cellAttr['spacing']);
            }
            // then active cells
            $pos = $cellAttr['spacing'];
            for ($i=0; $i<$cellCount; $i++) {
                $progressHtml .= $tabs . $tab . $tab;
                $progressHtml .= "<div id=\"". $progressId . sprintf($cellAttr['id'],$i) ."A\""; 
                $progressHtml .= " class=\"".$cellAttr['class']."A\"";
                $progressHtml .= " style=\"position:absolute;top:".$cellAttr['spacing']."px;left:".$pos."px;";
                if (isset($cellAttr[$i])) {
                    $progressHtml .= "color:".$cellAttr[$i]['color'].";\"";
                } else {
                    $progressHtml .= "\"";
                }
                $progressHtml .= ">&nbsp;</div>" . $lnEnd;
                $pos += ($cellAttr['width'] + $cellAttr['spacing']);
            }
        } else {
            // inactive cells first
            $pos = $cellAttr['spacing'];
            for ($i=$cellCount-1; $i>=0; $i--) {
                $progressHtml .= $tabs . $tab . $tab;
                $progressHtml .= "<div id=\"". $progressId . sprintf($cellAttr['id'],$i) ."I\"";
                $progressHtml .= " class=\"".$cellAttr['class']."I\"";
                $progressHtml .= " style=\"position:absolute;top:".$cellAttr['spacing']."px;left:".$pos."px;\"";
                $progressHtml .= ">&nbsp;</div>" . $lnEnd;
                $pos += ($cellAttr['width'] + $cellAttr['spacing']);
            }
            // then active cells
            $pos = $cellAttr['spacing'];
            for ($i=$cellCount-1; $i>=0; $i--) {
                $progressHtml .= $tabs . $tab . $tab;
                $progressHtml .= "<div id=\"". $progressId . sprintf($cellAttr['id'],$i) ."A\"";
                $progressHtml .= " class=\"".$cellAttr['class']."A\"";
                $progressHtml .= " style=\"position:absolute;top:".$cellAttr['spacing']."px;left:".$pos."px;";
                if (isset($cellAttr[$i])) {
                    $progressHtml .= "color:".$cellAttr[$i]['color'].";\"";
                } else {
                    $progressHtml .= "\"";
                }
                $progressHtml .= ">&nbsp;</div>" . $lnEnd;
                $pos += ($cellAttr['width'] + $cellAttr['spacing']);
            }
        }
        return $progressHtml;
    }

    /**
     * Returns a vertical progress bar structure as HTML.
     *
     * @return     string                   Vertical HTML Progress bar
     * @since      1.0
     * @access     private
     */
    function _getProgressVbar_toHtml()
    {
        $ui =& $this->_UI;
        $tabs = $ui->_getTabs();
        $tab = $ui->_getTab();
        $lnEnd = $ui->_getLineEnd();
        $way_natural = ($ui->getFillWay() == 'natural');
        $cellAttr = $ui->getCellAttributes();
        $cellCount = $ui->getCellCount();

        $progressId = $this->getIdent().'_';
        $progressHtml = "";

        if ($way_natural) {
            // inactive cells first
            $pos = $cellAttr['spacing'];
            for ($i=$cellCount-1; $i>=0; $i--) {
                $progressHtml .= $tabs . $tab . $tab;
                $progressHtml .= "<div id=\"". $progressId . sprintf($cellAttr['id'],$i) ."I\"";
                $progressHtml .= " class=\"".$cellAttr['class']."I\"";
                $progressHtml .= " style=\"position:absolute;left:".$cellAttr['spacing']."px;top:".$pos."px;\"";
                $progressHtml .= ">&nbsp;</div>" . $lnEnd;
                $pos += ($cellAttr['height'] + $cellAttr['spacing']);
            }
            // then active cells
            $pos = $cellAttr['spacing'];
            for ($i=$cellCount-1; $i>=0; $i--) {
                $progressHtml .= $tabs . $tab . $tab;
                $progressHtml .= "<div id=\"". $progressId . sprintf($cellAttr['id'],$i) ."A\"";
                $progressHtml .= " class=\"".$cellAttr['class']."A\"";
                $progressHtml .= " style=\"position:absolute;left:".$cellAttr['spacing']."px;top:".$pos."px;";
                if (isset($cellAttr[$i])) {
                    $progressHtml .= "color:".$cellAttr[$i]['color'].";\"";
                } else {
                    $progressHtml .= "\"";
                }
                $progressHtml .= ">&nbsp;</div>" . $lnEnd;
                $pos += ($cellAttr['height'] + $cellAttr['spacing']);
            }
        } else {
            // inactive cells first
            $pos = $cellAttr['spacing'];
            for ($i=0; $i<$cellCount; $i++) {
                $progressHtml .= $tabs . $tab . $tab;
                $progressHtml .= "<div id=\"". $progressId . sprintf($cellAttr['id'],$i) ."I\"";
                $progressHtml .= " class=\"".$cellAttr['class']."I\"";
                $progressHtml .= " style=\"position:absolute;left:".$cellAttr['spacing']."px;top:".$pos."px;\"";
                $progressHtml .= ">&nbsp;</div>" . $lnEnd;
                $pos += ($cellAttr['height'] + $cellAttr['spacing']);
            }
            // then active cells
            $pos = $cellAttr['spacing'];
            for ($i=0; $i<$cellCount; $i++) {
                $progressHtml .= $tabs . $tab . $tab;
                $progressHtml .= "<div id=\"". $progressId . sprintf($cellAttr['id'],$i) ."A\"";
                $progressHtml .= " class=\"".$cellAttr['class']."A\"";
                $progressHtml .= " style=\"position:absolute;left:".$cellAttr['spacing']."px;top:".$pos."px;";
                if (isset($cellAttr[$i])) {
                    $progressHtml .= "color:".$cellAttr[$i]['color'].";\"";
                } else {
                    $progressHtml .= "\"";
                }
                $progressHtml .= ">&nbsp;</div>" . $lnEnd;
                $pos += ($cellAttr['height'] + $cellAttr['spacing']);
            }
        }
        return $progressHtml;
    }

    /**
     * Returns a polygonal progress structure as HTML.
     *
     * @return     string                   Polygonal HTML Progress 
     * @since      1.2.0RC1
     * @access     private
     */
    function _getProgressPolygonal_toHtml()
    {
        $ui =& $this->_UI;
        $tabs = $ui->_getTabs();
        $tab = $ui->_getTab();
        $lnEnd = $ui->_getLineEnd();
        $way_natural = ($ui->getFillWay() == 'natural');
        $cellAttr = $ui->getCellAttributes();
        $cellCount = $ui->getCellCount();
        $coord = $ui->_coordinates;

        $progressId = $this->getIdent().'_';
        $progressHtml = "";

        if ($way_natural) {
            // inactive cells first
            for ($i=0; $i<$cellCount; $i++) {
                $top  = $coord[$i][0] * $cellAttr['width'];
                $left = $coord[$i][1] * $cellAttr['height'];
                $progressHtml .= $tabs . $tab . $tab;
                $progressHtml .= "<div id=\"". $progressId . sprintf($cellAttr['id'],$i) ."I\""; 
                $progressHtml .= " class=\"".$cellAttr['class']."I\"";
                $progressHtml .= " style=\"position:absolute;top:".$top."px;left:".$left."px;\"";
                $progressHtml .= ">&nbsp;</div>" . $lnEnd;
            }
            // then active cells
            for ($i=0; $i<$cellCount; $i++) {
                $top  = $coord[$i][0] * $cellAttr['width'];
                $left = $coord[$i][1] * $cellAttr['height'];
                $progressHtml .= $tabs . $tab . $tab;
                $progressHtml .= "<div id=\"". $progressId . sprintf($cellAttr['id'],$i) ."A\""; 
                $progressHtml .= " class=\"".$cellAttr['class']."A\"";
                $progressHtml .= " style=\"position:absolute;top:".$top."px;left:".$left."px;\"";
                if (isset($cellAttr[$i])) {
                    $progressHtml .= "color:".$cellAttr[$i]['color'].";\"";
                } else {
                    $progressHtml .= "\"";
                }
                $progressHtml .= ">&nbsp;</div>" . $lnEnd;
            }
        } else {
            $c = count($coord) - 1;
            // inactive cells first
            for ($i=0; $i<$cellCount; $i++) {
                $top  = $coord[$c-$i][0] * $cellAttr['width'];
                $left = $coord[$c-$i][1] * $cellAttr['height'];
                $progressHtml .= $tabs . $tab . $tab;
                $progressHtml .= "<div id=\"". $progressId . sprintf($cellAttr['id'],$i) ."I\""; 
                $progressHtml .= " class=\"".$cellAttr['class']."I\"";
                $progressHtml .= " style=\"position:absolute;top:".$top."px;left:".$left."px;\"";
                $progressHtml .= ">&nbsp;</div>" . $lnEnd;
            }
            // then active cells
            for ($i=0; $i<$cellCount; $i++) {
                $top  = $coord[$c-$i][0] * $cellAttr['width'];
                $left = $coord[$c-$i][1] * $cellAttr['height'];
                $progressHtml .= $tabs . $tab . $tab;
                $progressHtml .= "<div id=\"". $progressId . sprintf($cellAttr['id'],$i) ."A\""; 
                $progressHtml .= " class=\"".$cellAttr['class']."A\"";
                $progressHtml .= " style=\"position:absolute;top:".$top."px;left:".$left."px;\"";
                if (isset($cellAttr[$i])) {
                    $progressHtml .= "color:".$cellAttr[$i]['color'].";\"";
                } else {
                    $progressHtml .= "\"";
                }
                $progressHtml .= ">&nbsp;</div>" . $lnEnd;
            }
        }
        return $progressHtml;
    }

    /**
     * Returns a circle progress structure as HTML.
     *
     * @return     string                   Circle HTML Progress 
     * @since      1.2.0RC1
     * @access     private
     */
    function _getProgressCircle_toHtml()
    {
        $ui =& $this->_UI;
        $tabs = $ui->_getTabs();
        $tab = $ui->_getTab();
        $lnEnd = $ui->_getLineEnd();
        $way_natural = ($ui->getFillWay() == 'natural');
        $cellAttr = $ui->getCellAttributes();
        $cellCount = $ui->getCellCount();

        $progressId = $this->getIdent().'_';
        $progressHtml = "";

        if ($way_natural) {
            // inactive cells first
            for ($i=0; $i<$cellCount; $i++) {
                $progressHtml .= $tabs . $tab . $tab;
                $progressHtml .= "<div id=\"". $progressId . sprintf($cellAttr['id'],$i) ."I\""; 
                $progressHtml .= " class=\"".$cellAttr['class']."I\"";
                $progressHtml .= " style=\"position:absolute;top:0;left:0;\"";
                $progressHtml .= ">&nbsp;</div>" . $lnEnd;
            }
            // then active cells
            for ($i=0; $i<$cellCount; $i++) {
                $progressHtml .= $tabs . $tab . $tab;
                $progressHtml .= "<div id=\"". $progressId . sprintf($cellAttr['id'],$i) ."A\""; 
                $progressHtml .= " class=\"".$cellAttr['class']."A\"";
                $progressHtml .= " style=\"position:absolute;top:0;left:0;\"";
                $progressHtml .= "><img src=\"".$cellAttr[$i+1]['background-image']."\" border=\"0\" /></div>" . $lnEnd;
            }
        } else {
            // inactive cells first
            for ($i=0; $i<$cellCount; $i++) {
                $progressHtml .= $tabs . $tab . $tab;
                $progressHtml .= "<div id=\"". $progressId . sprintf($cellAttr['id'],$i) ."I\""; 
                $progressHtml .= " class=\"".$cellAttr['class']."I\"";
                $progressHtml .= " style=\"position:absolute;top:0;left:0;\"";
                $progressHtml .= ">&nbsp;</div>" . $lnEnd;
            }
            // then active cells
            for ($i=0; $i<$cellCount; $i++) {
                $progressHtml .= $tabs . $tab . $tab;
                $progressHtml .= "<div id=\"". $progressId . sprintf($cellAttr['id'],$i) ."A\""; 
                $progressHtml .= " class=\"".$cellAttr['class']."A\"";
                $progressHtml .= " style=\"position:absolute;top:0;left:0;\"";
                $progressHtml .= "><img src=\"".$cellAttr[$i+1]['background-image']."\" border=\"0\" /></div>" . $lnEnd;
            }
        }
        return $progressHtml;
    }

    /**
     * Checks for callback function existance
     *
     * @param      mixed     $callback      a callback, like one used by call_user_func()
     *
     * @return     boolean
     * @since      1.2.0RC3
     * @access     private
     */
    function _callbackExists($callback)
    {
        if (is_string($callback)) {
            return function_exists($callback);
        } elseif (is_array($callback) && is_object($callback[0])) {
            return method_exists($callback[0], $callback[1]);
        } else {
            return false;
        }
    }

    /**
     * Initialize Error Stack engine
     *
     * @param      array     $prefs         hash of params for PEAR::Log object list
     *
     * @return     void
     * @since      1.2.0RC1
     * @access     private
     */
    function _initErrorStack($prefs = array())
    {
        $this->_package = 'HTML_Progress';
        $stack =& PEAR_ErrorStack::singleton($this->_package);

        if (isset($prefs['pushCallback']) && is_callable($prefs['pushCallback'])) {
            $cb = $prefs['pushCallback'];
        } else {
            $cb = array('HTML_Progress', '_handleError');
        }
        $stack->pushCallback($cb);

        if (isset($prefs['msgCallback'])) {
            $cb = $prefs['msgCallback'];
        } else {
            $cb = array('HTML_Progress', '_msgCallback');
        }
        $stack->setMessageCallback($cb);
        if (isset($prefs['contextCallback'])) {
            $stack->setContextCallback($prefs['contextCallback']);
        }
        $messages = HTML_Progress::_getErrorMessage();
        $stack->setErrorMessageTemplate($messages);
        $composite = &Log::singleton('composite');
        $stack->setLogger($composite);

        $drivers = isset($prefs['handler']) ? $prefs['handler'] : array();
        $display_errors = isset($prefs['display_errors']) ? strtolower($prefs['display_errors']) : 'on';
        $log_errors = isset($prefs['log_errors']) ? strtolower($prefs['log_errors']) : 'on';
        
        foreach ($drivers as $handler => $params) {
            if ((strtolower($handler) == 'display') && ($display_errors == 'off')) {
                continue;
            }
            if ((strtolower($handler) != 'display') && ($log_errors == 'off')) {
                continue;
            }       
            $name = isset($params['name']) ? $params['name'] : '';
            $ident = isset($params['ident']) ? $params['ident'] : '';
            $conf = isset($params['conf']) ? $params['conf'] : array();
            $level = isset($params['level']) ? $params['level'] : PEAR_LOG_DEBUG;
            
            $logger = &Log::singleton(strtolower($handler), $name, $ident, $conf, $level);
            $composite->addChild($logger);
        }

        // Add at least the Log::display driver to output errors on browser screen
        if (!array_key_exists('display', $drivers)) {
            if ($display_errors == 'on') {
                $logger = &Log::singleton('display');
                $composite->addChild($logger);
            }
        }
    }

    /**
     * User callback to generate error messages for any instance
     *
     * @param      object    $stack         PEAR_ErrorStack instance
     * @param      array     $err           current error with context info 
     *
     * @return     string
     * @since      1.2.0RC1
     * @access     private
     */
    function _msgCallback(&$stack, $err)
    {
        $message = call_user_func_array(array(&$stack, 'getErrorMessage'), array(&$stack, $err));

        if (isset($err['context']['function'])) {
            $message .= ' in ' . $err['context']['class'] . '::' . $err['context']['function'];
        }
        if (isset($err['context']['file'])) {
            $message .= ' (file ' . $err['context']['file'] . ' at line ' . $err['context']['line'] .')';
        }
        return $message;
    }

    /**
     * Error Message Template array
     *
     * @return     string
     * @since      1.0
     * @access     private
     */
    function _getErrorMessage()
    {
        $messages = array(
            HTML_PROGRESS_ERROR_INVALID_INPUT =>
                'invalid input, parameter #%paramnum% '
                    . '"%var%" was expecting '
                    . '"%expected%", instead got "%was%"',
            HTML_PROGRESS_ERROR_INVALID_CALLBACK =>
                'invalid callback, parameter #%paramnum% '
                    . '"%var%" expecting %element%,'
                    . ' instead got "%was%" does not exists',
            HTML_PROGRESS_DEPRECATED => 
                'method is deprecated '
                    . 'use %newmethod% instead of %oldmethod%'

        );
        return $messages;
    }

    /**
     * Default internal error handler
     * Dies if the error is an exception (and would have died anyway)
     *
     * @since      1.2.0RC2
     * @access     private
     */
    function _handleError($err)
    {
        if ($err['level'] == 'exception') {
            $stack =& PEAR_ErrorStack::singleton($err['package']);
            $stack->_log($err);
            die();
        }
    }

    /**
     * Add an error to the stack
     * Dies if the error is an exception (and would have died anyway)
     *
     * @param      integer   $code       Error code.
     * @param      string    $level      The error level of the message. 
     *                                   Valid are PEAR_LOG_* constants
     * @param      array     $params     Associative array of error parameters
     * @param      boolean   $msg        Static error message
     *
     * @return     array     PEAR_ErrorStack instance. And with context info (if PHP 4.3+)
     * @since      1.2.0RC1
     * @access     public
     */
    function raiseError($code, $level, $params, $msg = false)
    {
        if (function_exists('debug_backtrace')) {
            $trace = debug_backtrace();     // PHP 4.3+
        } else {
            $trace = null;                  // PHP 4.1.x, 4.2.x (no context info available)
        }
        $err = PEAR_ErrorStack::staticPush($this->_package, $code, $level, $params, $msg, false, $trace);
        return $err;
    }

    /**
     * Determine whether there are any errors on the HTML_Progress stack
     *
     * @return     boolean
     * @since      1.2.0RC3
     * @access     public
     */
    function hasErrors()
    {
        $s = &PEAR_ErrorStack::singleton($this->_package);
        return $s->hasErrors();
    }

    /**
     * Pop an error off of the HTML_Progress stack
     * 
     * @return     false|array
     * @since      1.2.0RC3
     * @access     public
     */
    function getError()
    {
        $s = &PEAR_ErrorStack::singleton($this->_package);
        return $s->pop();
    }
}    
?>