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

use ICanBoogie\HTTP;
use ICanBoogie\HTTP\NotFound;
use ICanBoogie\HTTP\Request;
use ICanBoogie\Module\Descriptor;
use ICanBoogie\Operation\Failure;
use ICanBoogie\Operation\FailureEvent;
use ICanBoogie\Operation\GetFormEvent;
use ICanBoogie\Operation\FormNotFound;
use ICanBoogie\Operation\BeforeControlEvent;
use ICanBoogie\Operation\ControlEvent;
use ICanBoogie\Operation\BeforeValidateEvent;
use ICanBoogie\Operation\ValidateEvent;
use ICanBoogie\Operation\BeforeProcessEvent;
use ICanBoogie\Operation\ProcessEvent;

/**
 * An operation.
 *
 * @property-read Core $app
 * @property ActiveRecord $record The target active record object of the operation.
 * @property-read Request $request The request.
 * @property-read array $controls The controls to apply to the operation before it is processed.
 * @property-read bool $is_forwarded `true` if the operation is forwarded, `false` otherwise.
 */
abstract class Operation extends Object
{
	/**
	 * Defines the destination of a forwarded operation.
	 *
	 * @var string
	 */
	const DESTINATION = '_operation_destination';

	/**
	 * Defines the operation name of a forwarded operation.
	 *
	 * @var string
	 */
	const NAME = '_operation_name';

	/**
	 * Defines the key of the resource targeted by the operation.
	 *
	 * @var string
	 */
	const KEY = '_operation_key';

	/**
	 * Defines the session token to be matched.
	 *
	 * @var string
	 */
	const SESSION_TOKEN = '_session_token';

	const RESTFUL_BASE = '/api/';
	const RESTFUL_BASE_LENGTH = 5;

	/**
	 * Creates a {@link Operation} instance from the specified parameters.
	 *
	 * @inheritdoc
	 *
	 * @return Operation
	 */
	static public function from($properties = null, array $construct_args = [], $class_name = null)
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
	 * the {@link DESTINATION}, {@link NAME} and optionally {@link KEY} parameters are defined
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
	 * {@link KEY} parameters within the request parameters to create the Operation instance.
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
	 * @param Request $request The request parameters.
	 *
	 * @throws \BadMethodCallException when the destination module or the operation name is
	 * not defined for a module operation.
	 *
	 * @throws NotFound if the operation is not found.
	 *
	 * @return Operation|null The decoded operation or null if no operation was found.
	 */
	static protected function from_request(Request $request)
	{
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
					throw new NotFound(format('Unknown operation %operation.', [ 'operation' => $path ]));
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

				preg_match('#^([a-z\.\-]+)/(([^/]+)/)?([a-zA-Z0-9_\-]+)$#', substr($path, self::RESTFUL_BASE_LENGTH), $matches);

				if (!$matches)
				{
					throw new NotFound(format('Unknown operation %operation.', [ 'operation' => $path ]));
				}

				list(, $module_id, , $operation_key, $operation_name) = $matches;
			}

			if (empty(\ICanBoogie\app()->modules->descriptors[$module_id]))
			{
				throw new NotFound(format('Unknown operation %operation.', [ 'operation' => $path ]));
			}

			if ($operation_key)
			{
				$request[self::KEY] = $operation_key;
			}

			return static::from_module_request($request, $module_id, $operation_name);
		}

		$module_id = $request[self::DESTINATION];
		$operation_name = $request[self::NAME];

		if (!$module_id && !$operation_name)
		{
			return null;
		}
		else if (!$module_id)
		{
			throw new \BadMethodCallException("The operation's destination is required.");
		}
		else if (!$operation_name)
		{
			throw new \BadMethodCallException("The operation's name is required.");
		}

		return static::from_module_request($request, $module_id, $operation_name);
	}

	/**
	 * Tries to create an {@link Operation} instance from a route.
	 *
	 * @param HTTP\Request $request
	 * @param string $path An API path.
	 *
	 * @throws \Exception If the route controller fails to produce an {@link Operation} instance.
	 * @throws \InvalidArgumentException If the route's controller cannot be determined from the
	 * route definition.
	 *
	 * @return Operation|null
	 */
	static protected function from_route(Request $request, $path)
	{
		$app = \ICanBoogie\app();
		$route = $app->routes->find($path, $captured, $request->method, 'api');

		if (!$route)
		{
			return null;
		}

		#
		# We found a matching route. The arguments captured from the route are merged with
		# the request parameters. The route must define either a class for the operation
		# instance (defined using the `class` key) or a callback to create that instance
		# (defined using the `callback` key).
		#

		if ($captured)
		{
			if (isset($route->param_translation_list))
			{
				foreach ($route->param_translation_list as $from => $to)
				{
					$captured[$to] = $captured[$from];
				}
			}

			$request->path_params = $captured;
			$request->params = $captured + $request->params;

			if (isset($request->path_params[self::DESTINATION]))
			{
				$route->module = $request->path_params[self::DESTINATION];
			}
		}

		$controller = $route->controller;

		if (is_callable($controller))
		{
			$operation = call_user_func($controller, $request);
		}
		else if (!class_exists($controller, true))
		{
			throw new \Exception("Unable to instantiate operation, class not found: $controller.");
		}
		else
		{
			$operation = new $controller($request);
		}

		if (!($operation instanceof self))
		{
			throw new \Exception(format
			(
				'The controller for the route %route failed to produce an operation object, %rc returned.', [

					'route' => $path,
					'rc' => $operation
				]
			));
		}

		if (isset($route->module))
		{
			$operation->module = $app->modules[$route->module];
		}

		if (isset($request->path_params[self::KEY]))
		{
			$operation->key = $request->path_params[self::KEY];
		}

		return $operation;
	}

	/**
	 * Creates an {@link Operation} instance from a module request.
	 *
	 * @param Request $request
	 * @param string $module_id
	 * @param string $operation_name
	 *
	 * @throws \Exception if the operation is not supported by the module.
	 *
	 * @return Operation
	 */
	static protected function from_module_request(Request $request, $module_id, $operation_name)
	{
		$module = \ICanBoogie\app()->modules[$module_id];
		$class = self::resolve_operation_class($operation_name, $module);

		if (!$class)
		{
			throw new \Exception(format
			(
				'The operation %operation is not supported by the module %module.', [

					'%module' => (string) $module,
					'%operation' => $operation_name
				]
			), 404);
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
	static public function encode($pattern, array $params=[])
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

		$rc = self::RESTFUL_BASE . strtr($pattern, [

			'{destination}' => $destination,
			'{name}' => $name,
			'{key}' => $key

		]) . ($qs ? '?' . $qs : '');

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
			$class = self::format_class_name($module->descriptor[Descriptor::NS], $name);

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
		return $namespace . '\\' . camelize(strtr($operation_name, '-', '_')) . 'Operation';
	}

	public $key;
	public $destination;

	/**
	 * @var \ICanBoogie\HTTP\Request The request triggering the operation.
	 */
	protected $request;

	protected function get_request()
	{
		return $this->request;
	}

	/**
	 * @var Operation\Response
	 */
	public $response;

	/**
	 * @var string
	 */
	public $method;

	const CONTROL_METHOD = 101;
	const CONTROL_SESSION_TOKEN = 102;
	const CONTROL_AUTHENTICATION = 103;
	const CONTROL_PERMISSION = 104;
	const CONTROL_RECORD = 105;
	const CONTROL_OWNERSHIP = 106;
	const CONTROL_FORM = 107;

	/**
	 * Returns the controls to pass.
	 *
	 * @return array All the controls set to false.
	 */
	protected function get_controls()
	{
		return [

			self::CONTROL_METHOD => false,
			self::CONTROL_SESSION_TOKEN => false,
			self::CONTROL_AUTHENTICATION => false,
			self::CONTROL_PERMISSION => false,
			self::CONTROL_RECORD => false,
			self::CONTROL_OWNERSHIP => false,
			self::CONTROL_FORM => false

		];
	}

	/**
	 * Getter for the {@link $record} property.
	 *
	 * @return ActiveRecord
	 */
	protected function lazy_get_record()
	{
		return $this->module->model[$this->key];
	}

	/**
	 * Returns the operation response.
	 *
	 * @return Operation\Response
	 */
	protected function get_response()
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
	protected function lazy_get_form()
	{
		$form = null;

		new GetFormEvent($this, $this->request, $form);

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
	protected function lazy_get_properties()
	{
		return [];
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

	protected function get_module()
	{
		return $this->module;
	}

	/**
	 * Returns `true` if the operation is forwarded.
	 *
	 * An operation is considered forwarded if the destination module and the operation name are
	 * defined in the request parameters. This is usually the case for forms which are posted
	 * on their URI but forwarded to a specified destination module.
	 *
	 * @return boolean
	 */
	protected function get_is_forwarded()
	{
		return !empty($this->request->request_params[Operation::NAME])
		&& !empty($this->request->request_params[Operation::DESTINATION]);
	}

	/**
	 * Constructor.
	 *
	 * The {@link $controls} property is unset in order for its getters to be called on the next
	 * access, while keeping its scope.
	 *
	 * @param Request $request @todo: should be a Request, but is sometimes a module.
	 */
	public function __construct($request = null)
	{
		unset($this->controls);

		if ($request instanceof Request)
		{
			if ($request[self::DESTINATION])
			{
				$this->module = $this->app->modules[$request[self::DESTINATION]];
			}
		}
		else if ($request instanceof Module)
		{
			$this->module = $request;
		}
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
	 * The response location
	 * ---------------------
	 *
	 * The `Location` header is used to ask the browser to load a different web page. This is often
	 * used to redirect the user when an operation has been performed e.g. creating/deleting a
	 * resource. The `location` property of the response is used to set that header. This is not
	 * a desirable behavior for XHR because although we might want to redirect the user, we still
	 * need to get the result of our request first. That is why when the `location` property is
	 * set, and the request is an XHR, the location is set to the `redirect_to` field and the
	 * `location` property is set to `null` to disable browser redirection.
	 *
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
	 *
	 * @throws Failure when the response has a client or server error, or the
	 * {@link \ICanBoogie\Operation\FormHasExpired} exception was raised.
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
			$control_payload = [ 'success' => &$control_success, 'controls' => &$controls, 'request' => $request ];

			new BeforeControlEvent($this, $control_payload);

			if ($control_success)
			{
				$control_success = $this->control($controls);
			}

			new ControlEvent($this, $control_payload);

			if (!$control_success)
			{
				new FailureEvent($this, FailureEvent::TYPE_CONTROL, $request);

				if (!$response->errors->count())
				{
					$response->errors[] = 'Operation control failed.';
				}
			}
			else
			{
				$validate_success = true;
				$validate_payload = [ 'success' => &$validate_success, 'errors' => &$response->errors, 'request' => $request ];

				new BeforeValidateEvent($this, $validate_payload);

				if ($validate_success)
				{
					$validate_success = $this->validate($response->errors);
				}

				new ValidateEvent($this, $validate_payload);

				if (!$validate_success || $response->errors->count())
				{
					new FailureEvent($this, FailureEvent::TYPE_VALIDATE, $request);

					if (!$response->errors->count())
					{
						$response->errors[] = 'Operation validation failed.';
					}
				}
				else
				{
					new BeforeProcessEvent($this, [ 'request' => $request, 'response' => $response, 'errors' => $response->errors ]);

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
		catch (\Exception $exception)
		{
			$code = $exception->getCode();

			if ($code < 200 || $code >= 600)
			{
				$code = 500;
			}

			$response->status = $code;
			$response->message = $exception->getMessage();
			$response['errors'] = [ '_base' => $exception->getMessage() ]; // COMPAT-20140310

			throw new Failure($this, $exception);
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
			$response->status->code = 400;
			$response->status->message = 'Operation failed';
		}
		else
		{
			new ProcessEvent($this, [ 'rc' => &$response->rc, 'response' => $response, 'request' => $request ]);
		}

		#
		# We log the `message` if the request is the main request and is not an XHR.
		#

		if ($response->message && !$request->parent && !$request->is_xhr)
		{
			log_success($response->message);
		}

		#
		# Operation\Request rewrites the response body if the body is null, but we only want that
		# for XHR request, so we need to set the response body to some value, which should be
		# the operation result, or an empty string of the request is redirected.
		#

		if ($request->is_xhr)
		{
			$response->content_type = $request->headers['Accept'];

			if ($response->location)
			{
				$response['redirect_to'] = $response->location;
			}

			$response->location = null;
		}
		else if ($response->location)
		{
			$response->body = '';
			$response->headers['Referer'] = $request->uri;
		}
		else if ($response->status->code == 304) // FIXME-20141009: is this still relevant ?
		{
			$response->body = '';
		}

		#
		# If the operation failed, we throw a Failure exception.
		#

		if ($response->status->is_client_error || $response->status->is_server_error)
		{
			throw new Failure($this);
		}

		return $response;
	}

	/**
	 * Format a string.
	 *
	 * @param string $format String format.
	 * @param array $args Format arguments.
	 * @param array $options I18n options.
	 *
	 * @return \ICanBoogie\I18n\FormattedString
	 */
	public function format($format, array $args = [], array $options = [])
	{
		return new \ICanBoogie\I18n\FormattedString($format, $args, $options);
	}

	/**
	 * Resets the operation state.
	 *
	 * A same operation object can be used multiple time to perform an operation with different
	 * parameters, this method is invoked to reset the operation state before it is controlled,
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
	 *     [
	 *         self::CONTROL_AUTHENTICATION => true,
	 *         self::CONTROL_RECORD => true,
	 *         self::CONTROL_FORM => false
	 *     ];
	 *
	 * Instead of a boolean, the "permission" control is enabled by a permission string or a
	 * permission level.
	 *
	 *     [
	 *         self::CONTROL_PERMISSION => Module::PERMISSION_MAINTAIN
	 *     ];
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
	 * The {@link record} property, or the {@link lazy_get_record()} getter, is used to get the
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
	 * @throws \Exception Depends on the control.
	 */
	protected function control(array $controls)
	{
		$controls += $this->controls;

		$method = $controls[self::CONTROL_METHOD];

		if ($method && !$this->control_method($method))
		{
			throw new \Exception(format("The %operation operation requires the %method method.", [

				'operation' => get_class($this),
				'method' => $method

			]));
		}

		if ($controls[self::CONTROL_SESSION_TOKEN] && !$this->control_session_token())
		{
			throw new \Exception("Session token doesn't match", 401);
		}

		if ($controls[self::CONTROL_AUTHENTICATION] && !$this->control_authentication())
		{
			throw new \Exception(format('The %operation operation requires authentication.', [

				'%operation' => get_class($this)

			]), 401);
		}

		if ($controls[self::CONTROL_PERMISSION] && !$this->control_permission($controls[self::CONTROL_PERMISSION]))
		{
			throw new \Exception(format("You don't have permission to perform the %operation operation.", [

				'%operation' => get_class($this)

			]), 401);
		}

		if ($controls[self::CONTROL_RECORD] && !$this->control_record())
		{
			throw new \Exception(format('Unable to retrieve record required for the %operation operation.', [

				'%operation' => get_class($this)

			]));
		}

		if ($controls[self::CONTROL_OWNERSHIP] && !$this->control_ownership())
		{
			throw new \Exception("You don't have ownership of the record.", 401);
		}

		if ($controls[self::CONTROL_FORM] && !$this->control_form())
		{
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
		$request = $this->request;

		return isset($request->request_params['_session_token']) && $request->request_params['_session_token'] == $this->app->session->token;
	}

	/**
	 * Controls the authentication of the user.
	 */
	protected function control_authentication()
	{
		return ($this->app->user_id != 0);
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
		return $this->app->user->has_permission($permission, $this->module);
	}

	/**
	 * Controls the ownership of the user over the operation target record.
	 *
	 * @return bool true if the user as ownership of the record or there is no record, false
	 * otherwise.
	 */
	protected function control_ownership()
	{
		$record = $this->record;

		return (!$record || $this->app->user->has_ownership($this->module, $record));
	}

	/**
	 * Checks if the operation target record exists.
	 *
	 * The method simply returns the {@link $record} property, which calls the
	 * {@link lazy_get_record()} getter if the property is not accessible.
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
	 * {@link lazy_get_form()} getter if the property is not accessible.
	 *
	 * @return bool true if the form exists and validates, false otherwise.
	 */
	protected function control_form()
	{
		$form = $this->form;

		if (!$form)
		{
			throw new FormNotFound($this);
		}

		return ($form && $form->validate($this->request->params, $this->response->errors));
	}

	/**
	 * Validates the operation before processing.
	 *
	 * The method is abstract and therefore must be implemented by subclasses.
	 *
	 * @param Errors $errors
	 *
	 * @return bool
	 */
	abstract protected function validate(Errors $errors);

	/**
	 * Processes the operation.
	 *
	 * The method is abstract and therefore must be implemented by subclasses.
	 *
	 * @return mixed According to the implementation.
	 */
	abstract protected function process();
}
