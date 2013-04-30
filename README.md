# Operation

Operations are the doers of the MOVE world. They are responsible for making changes to your models,
and for responding to events triggered by user interactions.





## Requirements

The package requires PHP 5.2 or later. The [icanboogie/http](https://packagist.org/packages/icanboogie/http)
package is also required.





## Installation

The recommended way to install this package is through [composer](http://getcomposer.org/).
Create a `composer.json` file and run `php composer.phar install` command to install it:

```json
{
    "minimum-stability": "dev",
    "require": {
		"icanboogie/operation": "dev-master"
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





## Documentation

The package is documented as part of the [ICanBoogie](http://icanboogie.org/) framework
[documentation](http://icanboogie.org/docs/). You can generate the documentation for the package
and its dependencies with the `make doc` command. The documentation is generated in the `docs`
directory. [ApiGen](http://apigen.org/) is required. You can later clean the directory with
the `make clean` command.





### Dispatcher

The package provides an HTTP dispatcher for operations. Although the operation might return an
_error_ response, the dispatcher only return a response when it is valid unless the request is an
XHR or an API operation (`/api/`). The dispatcher returns a 404 response if the operation returns
none. The dispatcher avoid returning an _error_ response to allow other dispatcher to handle the
request.

#### Rescuing the dispatch of an operation

If an exception is thrown during the dispatch of the operation a `rescue` event of class
[RescueEvent](http://icanboogie.org/docs/class-ICanBoogie.Operation.RescueEvent.html) is fired
upong the exception. The class extends the [RescueEvent](http://icanboogie.org/docs/class-ICanBoogie.Exception.RescueEvent.html)
with the operation object as property.





### Operation

Instances of [Operation](http://icanboogie.org/docs/class-ICanBoogie.Operation.html) represent
operations. Although the class provides many control methods and getters, the validation and
processing of the operation must be implemented by subclasses according to their desing.




#### Control

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

The controls definition is obtained though the `controls` magic property.

Once controls are cleared, the operation is validated, processed and a response is returned. The
following events are fired during the process:

- Before the control the event `ICanBoogie\Operation::control:before` of class [BeforeControlEvent](http://icanboogie.org/docs/class-ICanBoogie.Operation.BeforeControlEvent.html)
is fired. Third parties may use this event to alter the controls to run or clear them altogether.

- After the control the event `ICanBoogie\Operation::control` of class [ControlEvent](http://icanboogie.org/docs/class-ICanBoogie.Operation.ControlEvent.html)
is fired. Third parties may use this event to alter the outcome of the control.

- On failure the event `ICanBoogie\Operation::failure` of  class [FailureEvent](http://icanboogie.org/docs/class-ICanBoogie.Operation.FailureEvent.html)
is fired.

- The event `ICanBoogie\Operation::get_form` of class [GetFormEvent](http://icanboogie.org/docs/class-ICanBoogie.Operation.GetFormEvent.html)
is fired if the `form` getter is not overridden. It allows third parties to provide a form.





#### Validation

The operation needs to be validated before it is processed. The `validate()` method is invoked to
validate the operation. Errors should be collected in `$this->response->errors`. The validation
is considered failed if the method returns an empty value or errors are defined.

The following events are fired during the process:

- Before the validation the event `ICanBoogie\Operation::validate:before` of class [BeforeValidateEvent](http://icanboogie.org/docs/class-ICanBoogie.Operation.BeforeValidateEvent.html)
is fired. Third parties may use this event to alter the errors or the status of the validation.

- After the validation the event `ICanBoogie\Operation::validate` of class [ValidateEvent](http://icanboogie.org/docs/class-ICanBoogie.Operation.ValidateEvent.html)
is fired. Third parties may use this event to alter the errors or the outcome of the validation.

- On failure the event `ICanBoogie\Operation::failure` of  class [FailureEvent](http://icanboogie.org/docs/class-ICanBoogie.Operation.FailureEvent.html)
is fired.





#### Processing

After the control and the validation, the operation is finally processed by invoking its
`process()` method. The processing of the operation is considered failed if the method returns
`null` or errors are defined.

The following events are fired during the process:

- Before the processing the event `ICanBoogie\Operation::process:before` of class [BeforeProcessEvent](http://icanboogie.org/docs/class-ICanBoogie.Operation.BeforeProcessEvent.html)
is fired. Third parties may use this event to alter the request, response or errors.

- After the processing the event `ICanBoogie\Operation::process` of class [ProcessEvent](http://icanboogie.org/docs/class-ICanBoogie.Operation.ProcessEvent.html)
is fired. Third parties may use this event to alter the result, request or response.





## License

ICanBoogie/Operation is licensed under the New BSD License - See the LICENSE file for details.