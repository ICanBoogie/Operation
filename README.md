# Operation

[![Release](https://img.shields.io/packagist/v/icanboogie/operation.svg)](https://packagist.org/packages/icanboogie/operation)
[![Build Status](https://img.shields.io/travis/ICanBoogie/Operation/4.0.svg)](http://travis-ci.org/ICanBoogie/Operation)
[![HHVM](https://img.shields.io/hhvm/icanboogie/operation.svg)](http://hhvm.h4cc.de/package/icanboogie/operation)
[![Code Quality](https://img.shields.io/scrutinizer/g/ICanBoogie/Operation/4.0.svg)](https://scrutinizer-ci.com/g/ICanBoogie/Operation)
[![Code Coverage](https://img.shields.io/coveralls/ICanBoogie/Operation/4.0.svg)](https://coveralls.io/r/ICanBoogie/Operation)
[![Packagist](https://img.shields.io/packagist/dt/icanboogie/operation.svg)](https://packagist.org/packages/icanboogie/operation)

Operations are feature rich controllers dedicated to a single task, which often is
to create/update/delete records.





### Preamble

Events in this document are often referenced as `ICanBoogie\Operation::<event_type>`, where
`<event_type>` is the type of the event. For instance, `ICanBoogie\Operation::rescue` is an event
of type `rescue`, fired on an instance of `ICanBoogie\Operation`.  Now consider a `SaveOperation`
class inheriting from `ICanBoogie\Operation`. The `rescue` event
could also be fired on one of its instances, and an event hook could be attached to
`SaveOperation::rescue` to rescue the operation.

Because ICanBoogie's event system is based on class hierarchy an event hook attached to
`SaveOperation::rescue` is only invoked to rescue instances of `SaveOperation` and its subclasses,
whereas an event hook attached to `ICanBoogie\Operation::rescue` is invoked to rescue instances of
`ICanBoogie\Operation` and its subclasses, including `SaveOperation`.

Thus, when you see `ICanBoogie\Operation::rescue` read _"the event type 'rescue' fired on
an instance of the ICanBoogie\Operation subclass I want to listen to"_.

Please read the documentation of the [icanboogie/event] package for more details about the event
system.





## Operation

An instance of [Operation][] represents an operation. Although the class provides many control
methods and getters, the validation and processing of the operation must be implemented
by subclasses, according to their design.





### Controlling the operation

Before the operation can be validated and processed, controls are ran. The controls to run are
defined by the operation. The following controls are implemented and can be extended:

- `CONTROL_AUTHENTICATION`: Controls the authentication of the user.
- `CONTROL_PERMISSION`: Controls the permission of the user regarding the operation.
- `CONTROL_RECORD`: Controls the record associated with the operation. The getter `record` is used
to retrieve the record.
- `CONTROL_OWNERSHIP`: Controls the ownership of the record by the user.
- `CONTROL_FORM`: Controls the form associated with the operation. The getter `form` is used to
retrieve the form. [FormNotFound][] can be thrown if the form associated with the operation
cannot be found. [FormHasExpired][] can be thrown to indicate that the form associated with the
operation has expired.

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
`ICanBoogie\Operation::control:before` of class [BeforeControlEvent][] is fired. Third parties
may use this event to alter the controls to run, or clear them altogether.

- The event `ICanBoogie\Operation::control` of class [ControlEvent][] is fired after the control.
Third parties may use this event to alter the outcome of the control.

- On failure the event `ICanBoogie\Operation::failure` of class [FailureEvent][]
is fired, with its `type` property set to `control`.

- The event `ICanBoogie\Operation::get_form` of class [GetFormEvent][]
is fired if the `form` getter is not overridden. It allows third parties to provide a form to check
to parameters of the request.





### Validating the operation

The operation needs to be validated before it is processed. The `validate()` method is invoked to
validate the operation. Errors should be collected in the provided `$errors` collection. The
validation is considered failed if the method returns an empty value or errors are defined.

The following events are fired during the process:

- Before the validation the event `ICanBoogie\Operation::validate:before` of class [BeforeValidateEvent][]
is fired. Third parties may use this event to alter the errors or the status of the validation.

- After the validation the event `ICanBoogie\Operation::validate` of class [ValidateEvent][]
is fired. Third parties may use this event to alter the errors or the outcome of the validation.

- On failure the event `ICanBoogie\Operation::failure` of class [FailureEvent][] is fired.





### Processing the operation

After the control and the validation, the operation is finally processed by invoking its
`process()` method. The processing of the operation is considered failed if the method returns
`null` or errors are defined.

The following events are fired during the process:

- Before the processing, the event `ICanBoogie\Operation::process:before` of class
[BeforeProcessEvent][] is fired. Third parties may use this event to alter the request,
response or errors.

- After the processing, the event `ICanBoogie\Operation::process` of class [ProcessEvent][]
is fired. Third parties may use this event to alter the result, request or response.





### Handling failure

Exceptions thrown during the process (control/validation/processing) are caught and turned into
[Failure][] exceptions. The original exception is accessible using the `getPrevious()` method or
the `previous` property. The response of the operation is updated with the exception code and
message.

A [Failure][] exception is also thrown in the response has a client or server error, in which case
the exception is fired without a previous exception.

> **Note**: Failed operations may be rescued by the dispatcher.





## Forwarded operations

An operation is considered _forwarded_ when the actual destination and operation name is defined
using the request parameters [Operation::DESTINATION][] and [Operation::NAME][]. The URL of the request
is irrelevant to forwarded operations, moreover whether they succeed or fail the dispatch process
simply continues. For instance, this allows forms to be posted to their own _view_ URL (not the
URL of the operation) and displayed again if an error occurs.

> **Note**: Successful responses with a `location` are NOT discarded, they will redirect the request.

> **Note**: This feature is currently a foundation for the [icanboogie/module][] package and
its really that package who handles forwarded operations. This may change is the future.


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

$operation = new SaveOperation;
$response = $operation($request);

$operation->is_forwarded; // true
```





## Response

The response of the operation is represented by a [Response][] instance. The value returned by
the `process()` method is set to its `rc` property. The operation is considered failed if its
value is `null`, in which case the status of the operation is set to "400 Operation failed".





### Response location

The `Location` header is used to ask the browser to load a different web page. This is often
used to redirect the user when an operation has been performed e.g. creating/deleting a
resource. The `location` property of the response is used to set that header.





#### Response location and XHR

Redirecting a XHR is not a desirable behavior because although we might want to redirect the user,
we still need to get the result of our request first. In that case, the value of the `location`
property is moved to the `redirect_to` field and the `location` property is set to `null`.
Thus, the browser redirection is disabled, the response is returned and it's up to the developer
to choose if he should honor the redirection or not.





## Dispatcher

The package provides an HTTP dispatcher to dispatch operations. It should be placed at the top of
the dispatcher chain, before any routing. The dispatcher tries to create an `Operation` instance
from the specified request, and returns immediately if it fails.





### Handling of the operation response

The dispatcher discards responses from forwarded operations unless the request is an XHR or the
response has a location. Remember that failed operations throw a [Failure][] exception, which can
be rescued.





### Rescuing failed operations

If an exception is thrown during the dispatch of the operation, the dispatcher tries to rescue
it using the following steps:

1. The `ICanBoogie\Operation::rescue` event of class [RescueEvent][] is fired.
Event hooks attached to this event may replace the exception or provide a response. If a response
is provided it is returned.
2. Otherwise, if the exception is not an instance of [Failure][] the exception is
re-thrown.
3. Otherwise, if the request is an XHR the response of the operation is returned.
4. Otherwise, if the operation was forwarded the exception message is logged as an error
and the method returns.
5. Otherwise, the exception is re-thrown.

In summary, a failed operation is rescued if a response is provided during the
`ICanBoogie\Operation::rescue` event, or later if the request is an XHR. Although the rescue of an
operation might be successful, the returned response can be an error response.

> **Note:** If the operation is forwarded and the operation could not be rescued the request
dispatching process will simply continue.





## Defining operations

### Defining operations as routes

Because operations are controllers, they may be defined in the same fashion.

The following example demonstrates how the [module Nodes][] defines routes to set/unset the
`is_online` property:

```php
<?php

namespace Icybee\Modules\Nodes\Operation;

use ICanBoogie\HTTP\Request;
use ICanBoogie\Operation;

return [

    'api:nodes/online' => [

        'pattern' => '/api/:constructor/<nid:\d+>/is_online',
        'controller' => OnlineOperation::class,
        'via' => Request::METHOD_PUT,
        'param_translation_list' => [

            'constructor' => Operation::DESTINATION,
            'nid' => Operation::KEY

        ]
    ],

    'api:nodes/offline' => [

        'pattern' => '/api/:constructor/<nid:\d+>/is_online',
        'controller' => OfflineOperation::class,
        'via' => Request::METHOD_DELETE,
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
should be translated before the operation is executed. This handy feature allow routes to be
formatted from records, while providing mapping to operation key features.

```php
<?php

$node = $app->models['nodes']->one;
$path = $app->url_for('api:nodes/online', $node);
```





## Exceptions

The exception classes defined by the package implement the `ICanBoogie\Operation\Exception`
interface so that they can easily be identified:

```php
<?php

try
{
    // …
}
catch (\ICanBoogie\Operation\Exception $e)
{
    // an Operation exception
}
catch (\Throwable $e)
{
    // some other exception
}
```

The following exceptions are defined:

- [Failure][]: Exception raised when an operation fails.
- [FormHasExpired][]: Exception thrown when the form associated with an operation has expired.
- [FormNotFound][]: Exception thrown when the form associated with the operation cannot be found.





-----





## Requirements

The package requires PHP 7.2 or later.





## Installation

```bash
composer require icanboogie/operation
```





## Documentation

The package is documented as part of the [ICanBoogie][] framework
[documentation][]. You can generate the documentation for the package and its dependencies
with the `make doc` command. The documentation is generated in the `build/docs` directory.
[ApiGen](http://apigen.org/) is required. The directory can later be cleaned with the
`make clean` command.





## Testing

Run `make test-container` to create and log into the test container, then run `make test` to run the
test suite. Alternatively, run `make test-coverage` to run the test suite with test coverage. Open
`build/coverage/index.html` to see the breakdown of the code coverage.





## License

**icanboogie/operation** is released under the [New BSD License](LICENSE).





[documentation]:          https://icanboogie.org/api/operation/4.0/
[BeforeControlEvent]:     https://icanboogie.org/api/operation/4.0/class-ICanBoogie.Operation.BeforeControlEvent.html
[BeforeProcessEvent]:     https://icanboogie.org/api/operation/4.0/class-ICanBoogie.Operation.BeforeProcessEvent.html
[BeforeValidateEvent]:    https://icanboogie.org/api/operation/4.0/class-ICanBoogie.Operation.BeforeValidateEvent.html
[ControlEvent]:           https://icanboogie.org/api/operation/4.0/class-ICanBoogie.Operation.ControlEvent.html
[FailureEvent]:           https://icanboogie.org/api/operation/4.0/class-ICanBoogie.Operation.FailureEvent.html
[Failure]:                https://icanboogie.org/api/operation/4.0/class-ICanBoogie.Operation.Failure.html
[FormHasExpired]:         https://icanboogie.org/api/operation/4.0/class-ICanBoogie.Operation.FormHasExpired.html
[FormNotFound]:           https://icanboogie.org/api/operation/4.0/class-ICanBoogie.Operation.FormNotFound.html
[GetFormEvent]:           https://icanboogie.org/api/operation/4.0/class-ICanBoogie.Operation.GetFormEvent.html
[Operation]:              https://icanboogie.org/api/operation/4.0/class-ICanBoogie.Operation.html
[Operation::DESTINATION]: https://icanboogie.org/api/operation/4.0/class-ICanBoogie.Operation.html#DESTINATION
[Operation::NAME]:        https://icanboogie.org/api/operation/4.0/class-ICanBoogie.Operation.html#NAME
[ProcessEvent]:           https://icanboogie.org/api/operation/4.0/class-ICanBoogie.Operation.ProcessEvent.html
[RescueEvent]:            https://icanboogie.org/api/operation/4.0/class-ICanBoogie.Operation.RescueEvent.html
[Response]:               https://icanboogie.org/api/operation/4.0/class-ICanBoogie.Operation.Response.html
[ValidateEvent]:          https://icanboogie.org/api/operation/4.0/class-ICanBoogie.Operation.ValidateEvent.html

[module Nodes]:      https://github.com/Icybee/module-nodes
[icanboogie/event]:  https://github.com/ICanBoogie/Event
[icanboogie/module]: https://github.com/Icybee/Module
