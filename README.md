# Operation [![Build Status](https://travis-ci.org/ICanBoogie/Operation.png?branch=master)](https://travis-ci.org/ICanBoogie/Operation)

Operations are the doers of the MOVE world. They are responsible for making changes to your models,
and for responding to events triggered by user interactions.





## Operation

Instances of [Operation](http://icanboogie.org/docs/class-ICanBoogie.Operation.html) represent
operations. Although the class provides many control methods and getters, the validation and
processing of the operation must be implemented by subclasses according to their design.





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
		return array
		(
			self::CONTROL_PERMISSION => Module::PERMISSION_CREATE,
			self::CONTROL_RECORD => true,
			self::CONTROL_OWNERSHIP => true,
			self::CONTROL_FORM => true
		)

		+ parent::get_controls();
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




## Response

The response of the operation is represented by a [Response](http://icanboogie.org/docs/class-ICanBoogie.Operation.Response.html) instance.
The value returned by the `process()` method is set to its `rc` property. The operation
is considered failed if its value is `null`, in which case the status of the operation is set to
"400 Operation failed".





### Response location

The `Location` header is used to ask the browser to load a different web page. This is often
used to redirect the user when an operation has been performed e.g. creating/deleting a
resource. The `location` property of the response is used to set that header. This is not
a desirable behavior for XHR because although we might want to redirect the user, we still
need to get the result of our request first. That is why when the `location` property is
set, and the request is an XHR, the location is set to the `redirect_to` field and the
`location` property is set to `null` to disable browser redirection.





## Dispatcher

The package provides an HTTP dispatcher to dispatch operations. It should be placed at the top of
the dispatcher chain, before any routing. The dispatcher tries to create an Operation instance
from the specified Request, and returns immediatly if it fails.





### Handling of the operation response

If the operation returns an error response (client error or server error) and the request
is not an XHR nor an API request, `null` is returned instead of the response to allow another
dispatcher to handle the request, or display an error message.

If there is no response but the decontextualized request path is in the API
namespace (`/api/`), a 404 response is returned.





### Rescuing an operation

If an exception is thrown during the dispatch of the operation a `rescue` event of class
[RescueEvent](http://icanboogie.org/docs/class-ICanBoogie.Operation.RescueEvent.html) is fired
upon the exception. The class extends the [RescueEvent](http://icanboogie.org/docs/class-ICanBoogie.Exception.RescueEvent.html)
with the operation object.





## Requirements

The package requires PHP 5.2 or later.





## Installation

The recommended way to install this package is through [Composer](http://getcomposer.org/).
Create a `composer.json` file and run `php composer.phar install` command to install it:

```json
{
	"minimum-stability": "dev",
	"require": {
		"icanboogie/operation": "*"
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

[![Build Status](https://travis-ci.org/ICanBoogie/Operation.png?branch=master)](https://travis-ci.org/ICanBoogie/Operation)





## Documentation

The package is documented as part of the [ICanBoogie](http://icanboogie.org/) framework
[documentation](http://icanboogie.org/docs/). You can generate the documentation for the package
and its dependencies with the `make doc` command. The documentation is generated in the `docs`
directory. [ApiGen](http://apigen.org/) is required. You can later clean the directory with
the `make clean` command.





## License

ICanBoogie/Operation is licensed under the New BSD License - See the LICENSE file for details.