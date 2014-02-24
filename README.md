# Operation [![Build Status](https://travis-ci.org/ICanBoogie/Operation.png?branch=2.0)](https://travis-ci.org/ICanBoogie/Operation)

Operations are the doers of the MOVE world. They are responsible for making changes to your models,
and for responding to events triggered by user interactions.





## Operation

An instance of [Operation][] represents an operation. Although the class provides many control
methods and getters, the validation and processing of the operation must be implemented
by subclasses, according to their design.





### Control of the operation

Before the operation can be validated and processed, controls are ran. The controls to run are
defined by the operation. The following controls are implemented and can be extended:

- `CONTROL_AUTHENTICATION`: Controls the authentication of the user.
- `CONTROL_PERMISSION`: Controls the permission of the user regarding the operation.
- `CONTROL_RECORD`: Controls the record associated with the operation. The getter `record` is used
to retrieve the record.
- `CONTROL_OWNERSHIP`: Controls the ownership of the record by the user.
- `CONTROL_FORM`: Controls the form associated with the operation. The getter `form` is used to
retrieve the form. The exception [FormHasExpired](http://icanboogie.org/docs/class-ICanBoogie.Operation.FormHasExpired.html)
can be thrown to indicate that the form associated with the operation has expired.

The controls definition is obtained though the `controls` magic property:

```php
<?php

use ICanBoogie\Module;
use ICanBoogie\Operation;

class SaveOperation extends Operation
{
	protected function get_controls()
	{
		return [
		
			self::CONTROL_PERMISSION => Module::PERMISSION_CREATE,
			self::CONTROL_RECORD => true,
			self::CONTROL_OWNERSHIP => true,
			self::CONTROL_FORM => true
		
		] + parent::get_controls();
	}
}
```

The following events are fired during the process:

- Before the control of the operation by the `control()` method, the event
`ICanBoogie\Operation::control:before` of class [BeforeControlEvent](http://icanboogie.org/docs/class-ICanBoogie.Operation.BeforeControlEvent.html)
is fired. Third parties may use this event to alter the controls to run, or clear them altogether.

- The event `ICanBoogie\Operation::control` of class [ControlEvent](http://icanboogie.org/docs/class-ICanBoogie.Operation.ControlEvent.html)
is fired after the control. Third parties may use this event to alter the outcome of the control.

- On failure the event `ICanBoogie\Operation::failure` of class [FailureEvent](http://icanboogie.org/docs/class-ICanBoogie.Operation.FailureEvent.html)
is fired, with its `type` property set to `control`.

- The event `ICanBoogie\Operation::get_form` of class [GetFormEvent](http://icanboogie.org/docs/class-ICanBoogie.Operation.GetFormEvent.html)
is fired if the `form` getter is not overridden. It allows third parties to provide a form to check
to parameters of the request.





### Validation

The operation needs to be validated before it is processed. The `validate()` method is invoked to
validate the operation. Errors should be collected in the provided `$errors` collection. The
validation is considered failed if the method returns an empty value or errors are defined.

The following events are fired during the process:

- Before the validation the event `ICanBoogie\Operation::validate:before` of class [BeforeValidateEvent](http://icanboogie.org/docs/class-ICanBoogie.Operation.BeforeValidateEvent.html)
is fired. Third parties may use this event to alter the errors or the status of the validation.

- After the validation the event `ICanBoogie\Operation::validate` of class [ValidateEvent](http://icanboogie.org/docs/class-ICanBoogie.Operation.ValidateEvent.html)
is fired. Third parties may use this event to alter the errors or the outcome of the validation.

- On failure the event `ICanBoogie\Operation::failure` of class [FailureEvent](http://icanboogie.org/docs/class-ICanBoogie.Operation.FailureEvent.html)
is fired.





### Processing

After the control and the validation, the operation is finally processed by invoking its
`process()` method. The processing of the operation is considered failed if the method returns
`null` or errors are defined.

The following events are fired during the process:

- Before the processing the event `ICanBoogie\Operation::process:before` of class [BeforeProcessEvent](http://icanboogie.org/docs/class-ICanBoogie.Operation.BeforeProcessEvent.html)
is fired. Third parties may use this event to alter the request, response or errors.

- After the processing the event `ICanBoogie\Operation::process` of class [ProcessEvent](http://icanboogie.org/docs/class-ICanBoogie.Operation.ProcessEvent.html)
is fired. Third parties may use this event to alter the result, request or response.





### Forwarded operation

An operation is considered "forwarded" when the actual destination and operation name is defined
using the request parameters [Operation::DESTINATION](http://icanboogie.org/docs/class-ICanBoogie.Operation.html#DESTINATION)
and [Operation::NAME](http://icanboogie.org/docs/class-ICanBoogie.Operation.html#NAME). The URL of the request
is irrelevant to forwarded operation, more over whether they succeed or fail the dispatch process
simply continues. This allows forms to be posted to their own _view_ URL (not the URL of the
operation) and displayed again if an error occurs.

```php
<?php

use ICanBoogie\HTTP\Request;
use ICanBoogie\Operation;

$request = Request::from([

	'path' => '/',
	'request_params' => [
	
		Operation::DESTINATION => 'form',
		Operation::NAME => 'post',

		// …
	]
]);

$operation = Operation::from($request);

$operation->is_forwarded; // true
```

Note that successful responses with a location are NOT discarted.






## Response

The response of the operation is represented by a [Response](http://icanboogie.org/docs/class-ICanBoogie.Operation.Response.html) instance.
The value returned by the `process()` method is set to its `rc` property. The operation
is considered failed if its value is `null`, in which case the status of the operation is set to
"400 Operation failed".





### Response location

The `Location` header is used to ask the browser to load a different web page. This is often
used to redirect the user when an operation has been performed e.g. creating/deleting a
resource. The `location` property of the response is used to set that header.





#### Response location and XHR

Redirecting a XHR is not a desirable behavior because although we might want to redirect the user,
we still need to get the result of our request first. In that case, the value of the `location`
property is moved to the `redirect_to` field and the `location` property is set to `null`.
Thus, the browser redirection is disabled, the response is returned and it's up to the developper
to choose if he should honor the redirection or not.





## Dispatcher

The package provides an HTTP dispatcher to dispatch operations. It should be placed at the top of
the dispatcher chain, before any routing. The dispatcher tries to create an `Operation` instance
from the specified request, and returns immediatly if it fails.





### Handling of the operation response

The dispatcher discarts responses from forwarded operations unless the request is an XHR or the
response has a location. Remember that failed operations throw a [Failure](http://icanboogie.org/docs/class-ICanBoogie.Operation.Failure.html)
exception, which can be rescued.





### Rescuing an operation

If an exception is thrown during the dispatch of the operation a `rescue` event of class
[RescueEvent](http://icanboogie.org/docs/class-ICanBoogie.Operation.RescueEvent.html) is fired
upon the exception. The class extends the [RescueEvent](http://icanboogie.org/docs/class-ICanBoogie.Exception.RescueEvent.html)
with the operation object.

A third party may produce a response or a new exception.





#### Rescuing a `Failure` exception

A [Failure](http://icanboogie.org/docs/class-ICanBoogie.Operation.Failure.html) exception is
thrown when the operation response code is a client error or a server
error. The exception might be rescued by the dispatcher in the following cases:

- The request is an XHR: the response of the operation is returned.

- The operation was _forwarded_ (see above): the exception is discarted and the dispatch of the
request continues.





## Defining operations

### Defining operations as routes

Operations can be defined as route controllers. Because the operation dispatcher is executed
before the routing dispatcher, operation defined as routes are always executed before regular
routes.

The following example demonstrates how the [module Nodes][] defines routes to set/unset the
`is_online` property:

```php
<?php

namespace Icybee\Modules\Nodes;

use ICanBoogie\Operation;

return [

	'api:nodes/online' => [

		'pattern' => '/api/:constructor/<nid:\d+>/is_online',
		'controller' =>__NAMESPACE__ . '\OnlineOperation',
		'via' => 'PUT',
		'param_translation_list' => [

			'constructor' => Operation::DESTINATION,
			'nid' => Operation::KEY

		]
	],

	'api:nodes/offline' => [

		'pattern' => '/api/:constructor/<nid:\d+>/is_online',
		'controller' =>__NAMESPACE__ . '\OfflineOperation',
		'via' => 'DELETE',
		'param_translation_list' => [

			'constructor' => Operation::DESTINATION,
			'nid' => Operation::KEY

		]
	]

];
```

The class of the operation is defined as the controller of the route. Notice how the request
method is used for the same route to distinguish the operation type.

The `param_translation_list` array is used to define how params captured from the pathinfo
should be renamed before the operation is created. This handy feature allow routes to be
formated from records, while providing mapping to operation key features.

```php
<?php

$node = $core->models['nodes']->one;
$path = $core->routes['api:nodes/online']->format($node);
```





-----





## Requirements

The package requires PHP 5.2 or later.





## Installation

The recommended way to install this package is through [Composer](http://getcomposer.org/).
Create a `composer.json` file and run `php composer.phar install` command to install it:

```json
{
	"minimum-stability": "dev",
	"require": {
		"icanboogie/operation": "2.x"
	}
}
```





### Cloning the repository

The package is [available on GitHub](https://github.com/ICanBoogie/Operation), its repository can
be cloned with the following command line:

	$ git clone git://github.com/ICanBoogie/Operation.git





## Testing

The test suite is ran with the `make test` command. [Composer](http://getcomposer.org/) is
automatically installed as well as all dependencies required to run the suite. You can later
clean the directory with the `make clean` command.

The package is continuously tested by [Travis CI](http://about.travis-ci.org/).

[![Build Status](https://travis-ci.org/ICanBoogie/Operation.png?branch=2.0)](https://travis-ci.org/ICanBoogie/Operation)





## Documentation

The package is documented as part of the [ICanBoogie](http://icanboogie.org/) framework
[documentation](http://icanboogie.org/docs/). You can generate the documentation for the package
and its dependencies with the `make doc` command. The documentation is generated in the `docs`
directory. [ApiGen](http://apigen.org/) is required. You can later clean the directory with
the `make clean` command.





## License

ICanBoogie/Operation is licensed under the New BSD License - See the LICENSE file for details.




[module Nodes]: https://github.com/Icybee/module-nodes
[Operation]: http://icanboogie.org/docs/class-ICanBoogie.Operation.html