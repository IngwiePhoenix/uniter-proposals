# Concept

Okay, so to put it simple... I am appearently not ready to leave my PHP-days behind me. But I also don't want to give up the great advantages of PHP while working with NodeJS - which is why I want to bring the best of PHP to Node, by supporting Uniter and proposing new features! :)

## The Uniter-MVC: UniterMPC

MPC stands for:
- (M)odel
- (P)resenter
- (C)ontroller

A regular MVC uses templates or static files in order to render views - but this framework is meant to be able to also act as a REST layer, where the collected and gathered data has to be presented differently. In fact, you may want to utilize search engine optimization, which would require RDF/a, JSON-LD and technologies alike. A presenter is basically an advanced view - it consists of a class holding multiple methods to decide how things are being rendered. The rest is basicaly the same, just that an existing JavaScript ORM has to be ported over, to properly support the creation of models through the PHP class architecture.

Controllers and Models are pretty much the same as usual and before, only their way of creation is a little bit different. The only likely thing that is still the same is a controller...it does what a controller does: hold and execute business logic, that will manipulate one or many models and forms, as well as pick the proper presenter.

### The presenter's HTML rendering

By utilizing modules such as `jsdom` and `virtual-dom`, it actually is surprisingly easy to allow a JSX-ish syntax to be used with this. Even utilizing raw HTML or other templating engines normally available only to JavaScript is possible. After all, the underlying VM (`phpcore` etc.) will essentially unwrap anything coming from the PHP-land - which means, that you can easily use existing JavaScript based rendering techniques, such as EJS or something else.

Before sending, the whole DOM is generated on the server-side, and then sent to the client - after all, PHP is, by tradition, a multipage application. However, this behaviour is going to be turned off in PJAX or when an alternative format has been choosen. Therefore, a presenter may support multiple ways of showing results - especially when talking about RESTful APIs. In this case, one may even create a trait and have that trait's function handle a standard API output creation, error handling and alike.

The router is the bridge between the client and the application, and therefore the router needs to be able to understand a presenter, and chose the correct way of presentation - as HTML, as JSON, as JSON-LD or maybe as XML, YAML, INI, MsgPack, bSON, or anything else that comes to your mind. Therefore, all methods of a presenter should be actual filetype names:

```php
class MyPresenter implements \UniterMPC\IPresenter {
    public function __toHTML() { ... }
    public function __toJSON() { ... }
    public function __toJSON_LD() {...}
    public function __toXML() { ... }
    public function __toWhateverYouLike() { ... }
}

// Within the router...
foreach($request->accepted as $mimeType) {
    $type = ucase(mime_to_extension($mimeType));
    $mtd = "__to${type}";
    if(in_array(class_methods($presenter), $type)) {
        $result = $presenter->{$mtd}();

        // $result is now an object of something.
        // Render that into a string by using the __toString method.
        // On HTML output, for instance, we may have generated a whole output
        // including the master. The returned object's toString method
        // will turn it into a string for us.
        $res->setContentType($mineType);
        $res->status(200);
        $res->send( (string)$result );
        break;
    }

    // Sanity check:
    if($res->hasSent())
        throw new \UniterMPC\Exceptions\PresentationNotImplemented($presenter);
}
```

Obviously, the above is a rough example. But it demonstrates picking up a rendering method proper for a specific request type.

### The good old PHP and the new and fresh ES6
The core power of Uniter is to unite PHP and JavaScript - and that is an OUTSTANDING selling point. It can be used on the server- but also on the client-side. For instance, if you are using React in your front-end to dynamicaly post-load data (progressive applications) and put them into the DOM, you can actually write your React components either in ES6, reuse previously used ones, or write them in Uniter PHP and it's XHP extension. How you do that, is up to you. But here is a conceptual example:

```php
import "Component" as class Component from "react";
class MyComponent extends Component {
    public function render() {
        return (<div>Hello!</div>);
    }
}
```

Flawlessly, right? But hey, you can use all the good things of react too. In fact, UniterMPC is meant to be run on two sides, the server AND the client.

### A framework to reduce filesizes and loadingtimes.
My (not just but mostly personal) problem with modern web frameworks is, well, that you can't tear them down and apart and just take what you like - you'll end up with a big bloat of stuff you are likely never going to use. Therefore, UniterMPC actually does only include the most mimimal stuff for you to use...and that is the `\JS\` namespace. This one reffers to the global scope:

```
<PHP> === <JavaScript>
\JS\$console === window.console
\JS\$navigator === window.navigator

// Works on Node too, by the way.
\JS\$process === process
```

If you want a router, you can just import one, if you want a virtual dom, import one! There are so many choices to pick from, that all that UniterMPC does, is to help you to forward calls on the client. For example, if you have multiple JS files, you may load and unload them by utilizing `<script>`-tags, HTTP headers (i.e. when sending HTML, send `X-Controller: /path/to/file.js` as well) or with other neat little things, such as WebSockets. Yes, controlling the client's javascript behavior can easily be implemented by using websockets. Even transporting new viewing data from the server, or accessing raw data in a Backbone-like manner, would easily be possible.

To be precice, here is what I mean. Let's look, what multipage apps do:
- Each page is loaded and generated on the server, then sent to the client.
- The resulting page is almost everything the client needs.
- The site can be completely seen, even when JavaScript is disabled in the browser.
- The client does not need too many resources, scripting-wise.

However, a multipage application has to reload itself over and over, which is where we can use PJAX to only request the view-fragments that we need, or even utilize a bare JSON-based REST API instead - allowing us an actual fallback:
- The browser does not have JS, or doesnt have the History API = The site will behave like an actual multi-page app.
- The browser does support History API, and therefore allows us to just use PJAX or a REST API and generate more on the client-side.

The ideal combination would be, to load an extended application shell - like with the actual and most important page contents, and then progressively add to it (modals, notification banners, counters, etc.) as the site progresses. Should the client navigate in a modern browser, we don't need to reload everything, we just PJAX-request the view fragment, insert it, and let that one set up and modify the URL by using the History API. Editing content and alike can easily be realized by using plain old AJAX, by allowing the client to actually access a model and use that. We don't need extra views just to perform CRUD, all we need is a little bit of smart coding ;)

```php
// Client
class MyForm extends ClientController {
    public function onLoad() {
        // pick up the form off the DOM.
        // Attach event listeners and alike!
        $this->form = \JS\$document->getElementById("myForm");
        $this->attachTo($this->form);

        // Add listeners to sub-elements.
        \UniterMPC\Utils::AttachListeners($this->form, [
            "input[name='myInput']" => [
                "change" => function($ev) {
                    // Implement input.on("change", fn)
                }
            ]
        ])
    }

    // Prefix controller methods just like on the server:
    // The prefix doesn't devine a HTTP method here,
    // but rather the element you'd like to manipulate :)
    // Make sure you attach elements to this controller first.
    // The attachment function will find functions possibly
    // related to an object whose ID matches the prefix, and then
    // attach the relevant listeners.
    // In here, we'll do the on("submit", fn) callback.
    // Oh also, Type Hinting is possible, technicaly.
    public function myForm_onSubmit(\JS\Event $ev) {
        $ev->preventDefault();

        // Assume you have an ORM that maps server-side models
        // to the front by using REST...
        $model = new MyFormModel();

        // Using: https://www.npmjs.com/package/parse-form
        // Imported as something like: formParse()
        $model->load(formParse($ev->target));

        if(!$model->validate()) {
            foreach($model->getValidationErrors() as $field => $message) {
                $el = $ev->target->children[$field];
                if(!$el->hasClass("error")) $el->addClass("error");
                $el_err = $ev->target->children["error_".$el->id];
                $el_err->innerText = $message;
            }
        } else {
            \UniterMPC\Router::postTo(
                "myContent/view",
                $ev->target->attr["action"]
            )->display();
        }
    }
}
```

In here, we are taking control over a whole form, allowing us to code all the events right inside one controller. We could even instantiate multiple controllers or components and then let them do specific handling. All of that is possible.

This would allow us to utilize a few aspects of a single-page application, such as:
- Using controllers and a router on the client side.
- Doing immediate validation in a way that we'd usually do it on the server.
- Accessing events and easily manipulating client-side runtime.

By sending as much as possible from the server, and doing a few manipulations on the client-side, we should be able to drastic code size reduction, and yet use aspects of both aproaches. Let's just call it an "enhanced multi-page application", as we can, if the browser supports it, actually become a single-page application too :)
