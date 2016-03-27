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
use ICanBoogie\HTTP\PermissionRequired;
use ICanBoogie\HTTP\Request;
use ICanBoogie\HTTP\Status;
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
use ICanBoogie\Routing\Controller;

/**
 * An operation.
 *
 * @method Operation\Response __invoke(Request $request)
 *
 * @property-read Core|\ICanBoogie\Binding\Routing\CoreBindings|Module\CoreBindings $app
 * @property ActiveRecord $record The target active record object of the operation.
 * @property-read Request $request The request.
 * @property-read array $controls The controls to apply to the operation before it is processed.
 * @property-read bool $is_forwarded `true` if the operation is forwarded, `false` otherwise.
 * @property Module $module
 */
abstract class Operation extends Controller
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
	 * Encodes a RESTful operation.
	 *
	 * @param string $pattern
	 * @param array $params
	 *
	 * @return string The operation encoded as a RESTful relative URL.
	 *
	 * @deprecated
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

	public $key;

	/**
	 * @var Operation\Response
	 */
	public $response;

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
		return $this->model[$this->key];
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
	 * Output format of the operation response.
	 *
	 * @var string
	 */
	protected $format;

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
	 * Handles the operation and returns a response.
	 *
 	 * The {@link $record} and {@link $form} properties are unset in order for their
	 * getters to be called on the next access, while keeping their scope.
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
	 *     /api/nodes/12/online.json
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
	protected function action(Request $request)
	{
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
				$code = Status::INTERNAL_SERVER_ERROR;
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
			$response->status->code = Status::BAD_REQUEST;
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
		else if ($response->status->code == Status::NOT_MODIFIED) // FIXME-20141009: is this still relevant ?
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
			throw new \Exception("Session token doesn't match", Status::UNAUTHORIZED);
		}

		if ($controls[self::CONTROL_AUTHENTICATION] && !$this->control_authentication())
		{
			throw new \Exception(format('The %operation operation requires authentication.', [

				'%operation' => get_class($this)

			]), Status::UNAUTHORIZED);
		}

		if ($controls[self::CONTROL_PERMISSION] && !$this->control_permission($controls[self::CONTROL_PERMISSION]))
		{
			throw new PermissionRequired(format("You don't have permission to perform the %operation operation.", [

				'%operation' => get_class($this)

			]), Status::UNAUTHORIZED);
		}

		if ($controls[self::CONTROL_RECORD] && !$this->control_record())
		{
			throw new NotFound(format('Unable to retrieve record required for the %operation operation.', [

				'%operation' => get_class($this)

			]));
		}

		if ($controls[self::CONTROL_OWNERSHIP] && !$this->control_ownership())
		{
			throw new \Exception("You don't have ownership of the record.", Status::UNAUTHORIZED);
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
	 *
	 * @TODO-20150816: should be a prototype method
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
	 *
	 * @TODO-20150816: should be a prototype method
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
	 *
	 * @TODO-20150816: should be a prototype method
	 */
	protected function control_ownership()
	{
		$record = $this->record;

		return (!$record || $this->app->user->has_ownership($record));
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
	 * @param ErrorCollection $errors
	 *
	 * @return ErrorCollection|bool
	 */
	abstract protected function validate(ErrorCollection $errors);

	/**
	 * Processes the operation.
	 *
	 * @return mixed According to the implementation.
	 */
	abstract protected function process();
}
