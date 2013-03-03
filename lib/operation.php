<?php

/*
 * This file is part of the ICanBoogie package.
 *
 * (c) Olivier Laviale <olivier.laviale@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ICanBoogie;

use ICanBoogie\Exception;
use ICanBoogie\HTTP;
use ICanBoogie\HTTP\HTTPError;
use ICanBoogie\HTTP\NotFound;
use ICanBoogie\HTTP\Request;

/**
 * An operation.
 *
 * @property ActiveRecord $record The target active record object of the operation.
 * @property-read Request $request The request.
 */
abstract class Operation extends Object
{
	const DESTINATION = '#destination';
	const NAME = '#operation';
	const KEY = '#key';
	const SESSION_TOKEN = '_session_token';

	const RESTFUL_BASE = '/api/';
	const RESTFUL_BASE_LENGTH = 5;

	static public function from($properties=null, array $construct_args=array(), $class_name=null)
	{
		if ($properties instanceof Request)
		{
			return static::from_request($properties);
		}

		return parent::from($properties, $construct_args, $class_name);
	}

	/**
	 * Creates an operation instance from a request.
	 *
	 * An operation can be defined as a route, in which case the path of the request starts with
	 * "/api/". An operation can also be defined using the request parameters, in which case
	 * the {@link DESTINATION}, {@link NAME} and optionaly {@link KEY} parameters are defined
	 * within the request parameters.
	 *
	 * When the operation is defined as a route, the method searches for a matching route.
	 *
	 * If a matching route is found, the captured parameters of the matching route are merged
	 * with the request parameters and the method tries to create an Operation instance using the
	 * route.
	 *
	 * If no matching route could be found, the method tries to extract the {@link DESTINATION},
	 * {@link NAME} and optional {@link KEY} parameters from the route using the
	 * `/api/:destination(/:key)/:name` pattern. If the route matches this pattern, captured
	 * parameters are merged with the request parameters and the operation decoding continues as
	 * if the operation was defined using parameters instead of the REST API.
	 *
	 * Finally, the method searches for the {@link DESTINATION}, {@link NAME} and optional
	 * {@link KEY} aparameters within the request parameters to create the Operation instance.
	 *
	 * If no operation was found in the request, the method returns null.
	 *
	 *
	 * Instancing using the matching route
	 * -----------------------------------
	 *
	 * The matching route must define either the class of the operation instance (by defining the
	 * `class` key) or a callback that would create the operation instance (by defining the
	 * `callback` key).
	 *
	 * If the route defines the instance class, it is used to create the instance. Otherwise, the
	 * callback is used to create the instance.
	 *
	 *
	 * Instancing using the request parameters
	 * ---------------------------------------
	 *
	 * The operation destination (specified by the {@link DESTINATION} parameter) is the id of the
	 * destination module. The class and the operation name (specified by the {@link NAME}
	 * parameter) are used to search for the corresponding operation class to create the instance:
	 *
	 *     ICanBoogie\<normalized_module_id>\<normalized_operation_name>Operation
	 *
	 * The inheritance of the module class is used the find a suitable class. For example,
	 * these are the classes tried for the "articles" module and the "save" operation:
	 *
	 *     ICanBoogie\Modules\Articles\SaveOperation
	 *     ICanBoogie\Modules\Contents\SaveOperation
	 *     ICanBoogie\Modules\Nodes\SaveOperation
	 *
	 * An instance of the found class is created with the request arguments and returned. If the
	 * class could not be found to create the operation instance, an exception is raised.
	 *
	 * @param string $uri The request URI.
	 * @param array $params The request parameters.
	 *
	 * @throws Exception When there is an error in the operation request.
	 *
	 * @return Operation|null The decoded operation or null if no operation was found.
	 */
	static protected function from_request(HTTP\Request $request)
	{
		global $core;

		$path = \ICanBoogie\Routing\decontextualize($request->path);
		$extension = $request->extension;

		if ($extension == 'json')
		{
			$path = substr($path, 0, -5);
			$request->headers['Accept'] = 'application/json';
			$request->headers['X-Requested-With'] = 'XMLHttpRequest'; // FIXME-20110925: that's not very nice
		}
		else if ($extension == 'xml')
		{
			$path = substr($path, 0, -4);
			$request->headers['Accept'] = 'application/xml';
			$request->headers['X-Requested-With'] = 'XMLHttpRequest'; // FIXME-20110925: that's not very nice
		}

		$path = rtrim($path, '/');

		if (substr($path, 0, self::RESTFUL_BASE_LENGTH) == self::RESTFUL_BASE)
		{
			$operation = static::from_route($request, $path);

			if ($operation)
			{
				return $operation;
			}

			if ($request->is_patch)
			{
				preg_match('#^([^/]+)/(\d+)$#', substr($path, self::RESTFUL_BASE_LENGTH), $matches);

				if (!$matches)
				{
					throw new NotFound(format('Unknown operation %operation.', array('operation' => $path)));
				}

				list(, $module_id, $operation_key) = $matches;

				$operation_name = 'patch';
			}
			else
			{
				#
				# We could not find a matching route, we try to extract the DESTINATION, NAME and
				# optional KEY from the URI.
				#

				preg_match('#^([a-z\.]+)/(([^/]+)/)?([a-zA-Z0-9_\-]+)$#', substr($path, self::RESTFUL_BASE_LENGTH), $matches);

				if (!$matches)
				{
					throw new NotFound(format('Unknown operation %operation.', array('operation' => $path)));
				}

				list(, $module_id, , $operation_key, $operation_name) = $matches;
			}

			if (empty($core->modules->descriptors[$module_id]))
			{
				throw new NotFound(format('Unknown operation %operation.', array('operation' => $path)));
			}

			$request[self::KEY] = $operation_key;

			return static::from_module_request($request, $module_id, $operation_name);
		}

		$module_id = $request[self::DESTINATION];
		$operation_name = $request[self::NAME];
		$operation_key = $request[self::KEY];

		if (!$module_id && !$operation_name)
		{
			return;
		}
		else if (!$module_id)
		{
			throw new Exception('The destination for the %operation operation is missing', array('%operation' => $operation_name));
		}
		else if (!$operation_name)
		{
			throw new Exception('The operation for the %module module is missing', array('%module' => $module_id));
		}

		unset($request[self::DESTINATION]);
		unset($request[self::NAME]);

		return static::from_module_request($request, $module_id, $operation_name);
	}

	static protected function from_route(HTTP\Request $request, $path)
	{
		$route = Routes::get()->find($path, $captured, $request->method, 'api');

		if (!$route)
		{
			return;
		}

		#
		# We found a matching route. The arguments captured from the route are merged with
		# the request parameters. The route must define either a class for the operation
		# instance (defined using the `class` key) or a callback to create that instance
		# (defined using the `callback` key).
		#

		if ($captured)
		{
			$request->path_params = $captured;
			$request->params = $captured + $request->params;
		}

		if ($route->controller)
		{
			$controller = $route->controller;

			if (is_callable($controller))
			{
				$operation = call_user_func($controller, $request);
			}
			else
			{
				$operation = new $controller($route); // TODO-20121119: should be $request instead of $route
			}

			if (!($operation instanceof self))
			{
				throw new Exception
				(
					'The controller for the route %route failed to produce an operation object, %rc returned.', array
					(
						'route' => $path,
						'rc' => $operation
					)
				);
			}
		}
		else
		{
			if ($route->callback)
			{
				throw new \InvalidArgumentException("'callback' is no longer supported, use 'controller'.");
			}
			else if ($route->class)
			{
				throw new \InvalidArgumentException("'class' is no longer supported, use 'controller'.");
			}

			throw new \InvalidArgumentException("'controller' is required.");
		}

		return $operation;
	}

	static protected function from_module_request(HTTP\Request $request, $module_id, $operation_name)
	{
		global $core;

		$module = $core->modules[$module_id];
		$class = self::resolve_operation_class($operation_name, $module);

		if (!$class)
		{
			throw new HTTPError(format('Uknown operation %operation for the %module module.', array('%module' => (string) $module, '%operation' => $operation_name)), 404);
		}

		return new $class($module);
	}

	/**
	 * Encodes a RESTful operation.
	 *
	 * @param string $pattern
	 * @param array $params
	 *
	 * @return string The operation encoded as a RESTful relative URL.
	 */
	static public function encode($pattern, array $params=array())
	{
		$destination = null;
		$name = null;
		$key = null;

		if (isset($params[self::DESTINATION]))
		{
			$destination = $params[self::DESTINATION];

			unset($params[self::DESTINATION]);
		}

		if (isset($params[self::NAME]))
		{
			$name = $params[self::NAME];

			unset($params[self::NAME]);
		}

		if (isset($params[self::KEY]))
		{
			$key = $params[self::KEY];

			unset($params[self::KEY]);
		}

		$qs = http_build_query($params, '', '&');

		$rc = self::RESTFUL_BASE . strtr
		(
			$pattern, array
			(
				'{destination}' => $destination,
				'{name}' => $name,
				'{key}' => $key
			)
		)

		. ($qs ? '?' . $qs : '');

		return \ICanBoogie\Routing\contextualize($rc);
	}

	/**
	 * Resolve operation class.
	 *
	 * The operation class name is resolved using the inherited classes for the target and the
	 * operation name.
	 *
	 * @param string $name Name of the operation.
	 * @param Module $target Target module.
	 *
	 * @return string|null The resolve class name, or null if none was found.
	 */
	static private function resolve_operation_class($name, Module $target)
	{
		$module = $target;

		while ($module)
		{
			$class = self::format_class_name($module->descriptor[Module::T_NAMESPACE], $name);

			if (class_exists($class, true))
			{
				return $class;
			}

			$module = $module->parent;
		}
	}

	/**
	 * Formats the specified namespace and operation name into an operation class.
	 *
	 * @param string $namespace
	 * @param string $operation_name
	 *
	 * @return string
	 */
	static public function format_class_name($namespace, $operation_name)
	{
		return $namespace . '\\' . ucfirst(camelize(strtr($operation_name, '_', '-'))) . 'Operation';
	}

	public $key;
	public $destination;

	/**
	 * @var \ICanBoogie\HTTP\Request The request triggering the operation.
	 */
	protected $request;

	protected function volatile_get_request()
	{
		return $this->request;
	}

	public $response;
	public $method;

	/**
	 * @var array Controls to pass before validation.
	 */
	protected $controls;

	const CONTROL_METHOD = 101;
	const CONTROL_SESSION_TOKEN = 102;
	const CONTROL_AUTHENTICATION = 103;
	const CONTROL_PERMISSION = 104;
	const CONTROL_RECORD = 105;
	const CONTROL_OWNERSHIP = 106;
	const CONTROL_FORM = 107;

	/**
	 * Getter for the {@link $controls} property.
	 *
	 * @return array All the controls set to false.
	 */
	protected function get_controls()
	{
		return array
		(
			self::CONTROL_METHOD => false,
			self::CONTROL_SESSION_TOKEN => false,
			self::CONTROL_AUTHENTICATION => false,
			self::CONTROL_PERMISSION => false,
			self::CONTROL_RECORD => false,
			self::CONTROL_OWNERSHIP => false,
			self::CONTROL_FORM => false
		);
	}

	/**
	 * Getter for the {@link $record} property.
	 *
	 * @return ActiveRecord
	 */
	protected function get_record()
	{
		return $this->module->model[$this->key];
	}

	/**
	 * Returns the operation response.
	 *
	 * @return Operation\Response
	 */
	protected function volatile_get_response()
	{
		return $this->response;
	}

	/**
	 * The form object of the operation.
	 *
	 * @var object
	 */
	protected $form;

	/**
	 * Getter for the {@link $form} property.
	 *
	 * The operation object fires a {@link GetFormEvent} event to retrieve the form. One can listen
	 * to the event to provide the form associated with the operation.
	 *
	 * One can override this method to provide the form using another method. Or simply define the
	 * {@link $form} property to circumvent the getter.
	 *
	 * @return object|null
	 */
	protected function get_form()
	{
		new Operation\GetFormEvent($this, $this->request, $form);

		return $form;
	}

	/**
	 * @var array The properties for the operation.
	 */
	protected $properties;

	/**
	 * Getter for the {@link $properties} property.
	 *
	 * The getter should only be called during the {@link process()} method.
	 *
	 * @return array
	 */
	protected function get_properties()
	{
		return array();
	}

	/**
	 * Output format of the operation response.
	 *
	 * @var string
	 */
	protected $format;

	/**
	 * Target module for the operation.
	 *
	 * The property is set by the constructor.
	 *
	 * @var Module
	 */
	protected $module;

	protected function volatile_get_module()
	{
		return $this->module;
	}

	/**
	 * Constructor.
	 *
	 * The {@link $controls} property is unset in order for its getters to be called on the next
	 * access, while keeping its scope.
	 *
	 * @param Module|array $destination The destination of the operation, either a module or a
	 * route.
	 */
	public function __construct($destination=null)
	{
		unset($this->controls);

		$this->module = $destination instanceof Module ? $destination : null;
	}

	/**
	 * Handles the operation and prints or returns its result.
	 *
 	 * The {@link $record}, {@link $form} and {@link $properties} properties are unset in order
 	 * for their getters to be called on the next access, while keeping their scope.
	 *
	 * The response object
	 * -------------------
	 *
	 * The operation result is saved in a _response_ object, which may contain meta data describing
	 * or accompanying the result. For example, the {@link Operation} class returns success and
	 * error messages in the {@link $message} and {@link $errors} properties.
	 *
	 * Depending on the `Accept` header of the request, the response object can be formatted as
	 * JSON or XML. If the `Accept` header is "application/json" the response is formatted as JSON.
	 * If the `Accept` header is "application/xml" the response is formatted as XML. If the
	 * `Accept` header is not of a supported type, only the result is printed, as a string.
	 *
	 * For API requests, the output format can also be defined by appending the corresponding
	 * extension to the request path:
	 *
	 *     /api/system.nodes/12/online.json
	 *
	 *
	 * Control, validation and processing
	 * ----------------------------------
	 *
	 * Before the operation is actually processed with the {@link process()} method, it is
	 * controlled and validated using the {@link control()} and {@link validate()} methods. If the
	 * control or validation fail the operation is not processed.
	 *
	 * The controls passed to the {@link control()} method are obtained through the
	 * {@link $controls} property or the {@link get_controls()} getter if the property is not
	 * accessible.
	 *
	 *
	 * Events
	 * ------
	 *
	 * The `failure` event is fired when the control or validation of the operation failed. The
	 * `type` property of the event is "control" or "validation" depending on which method failed.
	 * Note that the event won't be fired if an exception is thrown.
	 *
	 * The `process:before` event is fired with the operation as sender before the operation is
	 * processed using the {@link process()} method.
	 *
	 * The `process` event is fired with the operation as sender after the operation has been
	 * processed if its result is not `null`.
	 *
	 *
	 * Failed operation
	 * ----------------
	 *
	 * If the result of the operation is `null`, the operation is considered as failed, in which
	 * case the status code of the response is changed to 404 and the {@link ProcessEvent} is not
	 * fired.
	 *
	 * Note that exceptions are not caught by the method.
	 *
	 * @param HTTP\Request $request The request triggering the operation.
	 *
	 * @return Operation\Response The response of the operation.
	 */
	public function __invoke(HTTP\Request $request)
	{
		$this->request = $request;
		$this->reset();

		$rc = null;
		$response = $this->response;

		try
		{
			$controls = $this->controls;
			$control_success = true;
			$control_payload = array('success' => &$control_success, 'controls' => &$controls, 'request' => $request);

			new Operation\BeforeControlEvent($this, $control_payload);

			if ($control_success)
			{
				$control_success = $this->control($controls);
			}

			new Operation\ControlEvent($this, $control_payload);

			if (!$control_success)
			{
				new Operation\FailureEvent($this, array('type' => 'control', 'request' => $request));

				if (!$response->errors->count())
				{
					$response->errors[] = 'Operation control failed.';
				}
			}
			else
			{
				$validate_success = true;
				$validate_payload = array('success' => &$validate_success, 'errors' => &$response->errors, 'request' => $request);

				new Operation\BeforeValidateEvent($this, $validate_payload);

				if ($validate_success)
				{
					$validate_success = $this->validate($response->errors);
				}

				new Operation\ValidateEvent($this, $validate_payload);

				if (!$validate_success || $response->errors->count())
				{
					new Operation\FailureEvent($this, array('type' => 'validation', 'request' => $request));

					if (!$response->errors->count())
					{
						$response->errors[] = 'Operation validation failed.';
					}
				}
				else
				{
					new Operation\BeforeProcessEvent($this, array('request' => $request, 'response' => $response, 'errors' => $response->errors));

					if (!$response->errors->count())
					{
						$rc = $this->process();

						if ($rc === null && !$response->errors->count())
						{
							$response->errors[] = 'Operation failed (result was null).';
						}
					}
				}
			}
		}
		catch (Operation\FormHasExpired $e)
		{
			log_error($e->getMessage());

			return;
		}
		catch (\Exception $e)
		{
			throw $e;
		}

		$response->rc = $rc;

		#
		# errors
		#

		if ($response->errors->count() && !$request->is_xhr && !isset($this->form))
		{
			foreach ($response->errors as $error_message)
			{
				log_error($error_message);
			}
		}

		#
		# If the operation succeed (its result is not null), the ProcessEvent event is fired.
		# Listeners might use the event for further processing. For example, a _comment_ module
		# might delete the comments related to an _article_ module from which an article was
		# deleted.
		#

		if ($rc === null)
		{
			$response->status = array(400, 'Operation failed');
		}
		else
		{
			new Operation\ProcessEvent($this, array('rc' => &$response->rc, 'response' => $response, 'request' => $request));
		}

		#
		# We log the `message` if the request is the main request and is not an XHR.
		#

		if ($response->message && !$request->previous && !$request->is_xhr)
		{
			call_user_func_array('ICanBoogie\log_success', (array) $response->message);
		}

		/*
		 * Operation\Request rewrites the response body if the body is null, but we only want that
		 * for XHR request, so we need to set the response body to some value, which should be
		 * the operation result, or an empty string of the request is redirected.
		 */

		if ($request->is_xhr)
		{
			$response->content_type = $request->headers['Accept'];
			$response->location = null;
		}
		else if ($response->location)
		{
			$response->body = '';
			$response->headers['Referer'] = $request->uri;
		}
		else if ($response->status == 304)
		{
			$response->body = '';
		}
		else if (is_bool($response->rc))
		{
			return;
		}

		return $response;
	}

	/**
	 * Resets the operation state.
	 *
	 * A same operation object can be used multiple time to perform an operation with different
	 * parameters, this method is invoked to reset the operation state before it is controled,
	 * validated and processed.
	 */
	protected function reset()
	{
		$this->response = new Operation\Response;

		unset($this->form);
		unset($this->record);
		unset($this->properties);

		$key = $this->request[self::KEY];

		if ($key)
		{
			$this->key = $key;
		}
	}

	/**
	 * Controls the operation.
	 *
	 * A number of controls may be passed before an operation is validated and processed. Controls
	 * are defined as an array where the key is the control identifier, and the value defines
	 * whether the control is enabled. Controls are enabled by setting their value to true:
	 *
	 *     array
	 *     (
	 *         self::CONTROL_AUTHENTICATION => true,
	 *         self::CONTROL_RECORD => true,
	 *         self::CONTROL_FORM => false
	 *     );
	 *
	 * Instead of a boolean, the "permission" control is enabled by a permission string or a
	 * permission level.
	 *
	 *     array
	 *     (
	 *         self::CONTROL_PERMISSION => Module::PERMISSION_MAINTAIN
	 *     );
	 *
	 * The {@link $controls} property is used to get the controls or its magic getter
	 * {@link get_controls()} if the property is not accessible.
	 *
	 * Controls are passed in the following order:
	 *
	 * 1. CONTROL_SESSION_TOKEN
	 *
	 * Controls that '_session_token' is defined in $_POST and matches the current session's
	 * token. The {@link control_session_token()} method is invoked for this control. An exception
	 * with code 401 is thrown when the control fails.
	 *
	 * 2. CONTROL_AUTHENTICATION
	 *
	 * Controls the authentication of the user. The {@link control_authentication()} method is
	 * invoked for this control. An exception with the code 401 is thrown when the control fails.
	 *
	 * 3. CONTROL_PERMISSION
	 *
	 * Controls the permission of the guest or user. The {@link control_permission()} method is
	 * invoked for this control. An exception with code 401 is thrown when the control fails.
	 *
	 * 4. CONTROL_RECORD
	 *
	 * Controls the existence of the record specified by the operation's key. The
	 * {@link control_record()} method is invoked for this control. The value returned by the
	 * method is set in the operation object under the {@link record} property. The callback method
	 * must throw an exception if the record could not be loaded or the control of this record
	 * failed.
	 *
	 * The {@link record} property, or the {@link get_record()} getter, is used to get the
	 * record.
	 *
	 * 5. CONTROL_OWNERSHIP
	 *
	 * Controls the ownership of the user over the record loaded during the CONTROL_RECORD step.
	 * The {@link control_ownership()} method is invoked for the control. An exception with code
	 * 401 is thrown if the control fails.
	 *
	 * 6. CONTROL_FORM
	 *
	 * Controls the form associated with the operation by checking its existence and validity. The
	 * {@link control_form()} method is invoked for this control. Failing the control does not
	 * throw an exception, but a message is logged to the debug log.
	 *
	 * @param array $controls The controls to pass for the operation to be processed.
	 *
	 * @return boolean true if all the controls pass, false otherwise.
	 *
	 * @throws HTTPError Depends on the control.
	 */
	protected function control(array $controls)
	{
		$controls += $this->controls;

		$method = $controls[self::CONTROL_METHOD];

		if ($method && !$this->control_method($method))
		{
			throw new HTTPError
			(
				format("The %operation operation requires the %method method.", array
				(
					'operation' => get_class($this),
					'method' => $method
				))
			);
		}

		if ($controls[self::CONTROL_SESSION_TOKEN] && !$this->control_session_token())
		{
			throw new HTTPError("Session token doesn't match", 401);
		}

		if ($controls[self::CONTROL_AUTHENTICATION] && !$this->control_authentication())
		{
			throw new HTTPError
			(
				format('The %operation operation requires authentication.', array
				(
					'%operation' => get_class($this)
				)),

				401
			);
		}

		if ($controls[self::CONTROL_PERMISSION] && !$this->control_permission($controls[self::CONTROL_PERMISSION]))
		{
			throw new HTTPError
			(
				format("You don't have permission to perform the %operation operation.", array
				(
					'%operation' => get_class($this)
				)),

				401
			);
		}

		if ($controls[self::CONTROL_RECORD] && !$this->control_record())
		{
			throw new HTTPError
			(
				format('Unable to retrieve record required for the %operation operation.', array
				(
					'%operation' => get_class($this)
				))
			);
		}

		if ($controls[self::CONTROL_OWNERSHIP] && !$this->control_ownership())
		{
			throw new HTTPError("You don't have ownership of the record.", 401);
		}

		if ($controls[self::CONTROL_FORM] && !$this->control_form())
		{
			log('Control %control failed for operation %operation.', array('%control' => 'form', '%operation' => get_class($this)));

			return false;
		}

		return true;
	}

	/**
	 * Controls the request method.
	 *
	 * If the method is {@link Request::METHOD_ANY} it always matches.
	 *
	 * @param string $method
	 *
	 * @return boolean `true` if the method matches, `false` otherwise.
	 */
	protected function control_method($method)
	{
		return ($method === Request::METHOD_ANY) ? true : $method === $this->request->method;
	}

	/**
	 * Controls the session token.
	 *
	 * @return boolean true if the token is defined and correspond to the session token, false
	 * otherwise.
	 */
	protected function control_session_token()
	{
		global $core;

		$request = $this->request;

		return isset($request->request_params['_session_token']) && $request->request_params['_session_token'] == $core->session->token;
	}

	/**
	 * Controls the authentication of the user.
	 */
	protected function control_authentication()
	{
		global $core;

		return ($core->user_id != 0);
	}

	/**
	 * Controls the permission of the user for the operation.
	 *
	 * @param mixed $permission The required permission.
	 *
	 * @return bool true if the user has the specified permission, false otherwise.
	 */
	protected function control_permission($permission)
	{
		global $core;

		return $core->user->has_permission($permission, $this->module);
	}

	/**
	 * Controls the ownership of the user over the operation target record.
	 *
	 * @return bool true if the user as ownership of the record or there is no record, false
	 * otherwise.
	 */
	protected function control_ownership()
	{
		global $core;

		$record = $this->record;

		return (!$record || $core->user->has_ownership($this->module, $record));
	}

	/**
	 * Checks if the operation target record exists.
	 *
	 * The method simply returns the {@link $record} property, which calls the
	 * {@link get_record()} getter if the property is not accessible.
	 *
	 * @return ActiveRecord|null
	 */
	protected function control_record()
	{
		return $this->record;
	}

	/**
	 * Control the operation's form.
	 *
	 * The form is retrieved from the {@link $form} property, which invokes the
	 * {@link get_form()} getter if the property is not accessible.
	 *
	 * @return bool true if the form exists and validates, false otherwise.
	 */
	protected function control_form()
	{
		$form = $this->form;

		return ($form && $form->validate($this->request->params, $this->response->errors));
	}

	/**
	 * Validates the operation before processing.
	 *
	 * The method is abstract and therefore must be implemented by subclasses.
	 *
	 * @throws Exception If something horribly wrong happens.
	 *
	 * @return bool true if the operation is valid, false otherwise.
	 */
	abstract protected function validate(Errors $errors);

	/**
	 * Processes the operation.
	 *
	 * The method is abstract and therefore must be implemented by subclasses.
	 *
	 * @return mixed Depends on the implementation.
	 */
	abstract protected function process();
}

/*
 * Operation events
 */

namespace ICanBoogie\Operation;

use ICanBoogie\HTTP\Request;

abstract class ControlEventBase extends \ICanBoogie\Event
{
	/**
	 * Reference to the success result of the control.
	 *
	 * @var bool
	 */
	public $success;

	/**
	 * Reference to operation controls.
	 *
	 * @var array
	 */
	public $controls;

	/**
	 * The request that triggered the operation.
	 *
	 * @ var \ICanBoogie\HTTP\Request
	 */
	public $request;
}

/**
 * Event class for the `ICanBoogie\Operation::control:before` event.
 */
class BeforeControlEvent extends ControlEventBase
{
	/**
	 * The event is constructed with the type `control:before`.
	 *
	 * @param \ICanBoogie\Operation $target
	 * @param array $payload
	 */
	public function __construct(\ICanBoogie\Operation $target, array $payload)
	{
		parent::__construct($target, 'control:before', $payload);
	}
}

/**
 * Event class for the `ICanBoogie\Operation::control` event.
 */
class ControlEvent extends ControlEventBase
{
	/**
	 * The event is constructed with the type `control`.
	 *
	 * @param \ICanBoogie\Operation $target
	 * @param array $payload
	 */
	public function __construct(\ICanBoogie\Operation $target, array $payload)
	{
		parent::__construct($target, 'control', $payload);
	}
}

abstract class ValidateEventBase extends \ICanBoogie\Event
{
	/**
	 * Reference the success of the validation.
	 *
	 * @var bool
	 */
	public $success;

	/**
	 * Reference to the validation errors.
	 *
	 * @var \ICanBoogie\Errors
	 */
	public $errors;

	/**
	 * Request that triggered the operation.
	 *
	 * @var \ICanBoogie\HTTP\Request
	 */
	public $request;
}

/**
 * Event class for the `ICanBoogie\Operation::validate:before` event.
 */
class BeforeValidateEvent extends ValidateEventBase
{
	/**
	 * The event is constructed with the type `validate:before`.
	 *
	 * @param \ICanBoogie\Operation $target
	 * @param array $payload
	 */
	public function __construct(\ICanBoogie\Operation $target, array $payload)
	{
		parent::__construct($target, 'validate:before', $payload);
	}
}

/**
 * Event class for the `ICanBoogie\Operation::validate` event.
 */
class ValidateEvent extends ValidateEventBase
{
	/**
	 * The event is constructed with the type `validate`.
	 *
	 * @param \ICanBoogie\Operation $target
	 * @param array $payload
	 */
	public function __construct(\ICanBoogie\Operation $target, array $payload)
	{
		parent::__construct($target, 'validate', $payload);
	}
}

/**
 * Event class for the `ICanBoogie\Operation::failure` event.
 */
class FailureEvent extends \ICanBoogie\Event
{
	/**
	 * Type of failure, either `control` or `validation`.
	 *
	 * @var string
	 */
	public $type;

	/**
	 * The request that triggered the operation.
	 *
	 * @var \ICanBoogie\HTTP\Request
	 */
	public $request;

	/**
	 * The event is constructed with the type `failure`.
	 *
	 * @param \ICanBoogie\Operation $target
	 * @param array $payload
	 */
	public function __construct(\ICanBoogie\Operation $target, array $payload)
	{
		parent::__construct($target, 'failure', $payload);
	}
}

/**
 * Event class for the `ICanBoogie\Operation::process:before` event.
 */
class BeforeProcessEvent extends \ICanBoogie\Event
{
	/**
	 * The request that triggered the operation.
	 *
	 * @var \ICanBoogie\HTTP\Request
	 */
	public $request;

	/**
	 * The response of the operation.
	 *
	 * @var \ICanBoogie\HTTP\Response
	 */
	public $response;

	/**
	 * The errors collector.
	 *
	 * @var \ICanBoogie\Errors
	 */
	public $errors;

	/**
	 * The event is constructed with the type `process:before`.
	 *
	 * @param \ICanBoogie\Operation $target
	 * @param array $payload
	 */
	public function __construct(\ICanBoogie\Operation $target, array $payload)
	{
		parent::__construct($target, 'process:before', $payload);
	}
}

/**
 * Event class for the `ICanBoogie\Operation::process` event.
 */
class ProcessEvent extends \ICanBoogie\Event
{
	/**
	 * Reference to the response result property.
	 *
	 * @var mixed
	 */
	public $rc;

	/**
	 * The response object of the operation.
	 *
	 * @var \ICanBoogie\HTTP\Response
	 */
	public $response;

	/**
	 * The request that triggered the operation.
	 *
	 * @var \ICanBoogie\HTTP\Request
	 */
	public $request;

	/**
	 * The event is constructed with the type `process`.
	 *
	 * @param \ICanBoogie\Operation $target
	 * @param array $payload
	 */
	public function __construct(\ICanBoogie\Operation $target, array $payload)
	{
		parent::__construct($target, 'process', $payload);
	}
}

/**
 * Event class for the `ICanBoogie\Operation::get_form` event.
 */
class GetFormEvent extends \ICanBoogie\Event
{
	/**
	 * Reference to the result variable.
	 *
	 * @var mixed
	 */
	public $form;

	/**
	 * The request that triggered the operation.
	 *
	 * @var \ICanBoogie\HTTP\Request
	 */
	public $request;

	/**
	 * The event is constructed with the type `get_form`.
	 *
	 * @param \ICanBoogie\Operation $target
	 * @param array $payload
	 */
	public function __construct(\ICanBoogie\Operation $target, Request $request, &$form)
	{
		$this->request = $request;
		$this->form = &$form;

		parent::__construct($target, 'get_form');
	}
}

/**
 * Exception thrown when the form associated with an operation has expired.
 *
 * The exception is considered recoverable, if the request is not XHR.
 */
class FormHasExpired extends \Exception
{
	public function __construct($message="The form associated with the request has expired.", $code=500, $previous=null)
	{
		parent::__construct($message, $code, $previous);
	}
}