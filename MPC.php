<?php
/**
 * There are two options, that Uniter could possibly be used
 * in the context of a server-side application, especially a
 * website or webservice:
 * - Making all of Laravel/Lumen/Symfony work flawlessly
 * ... Or:
 * - Create an own little framework, base don the ideas of Laravel
 *   and the unique features and possibilities of Uniter.
 *
 * The latter is demonstrated here.
 *
 * MPC stands for:
 * - Model
 * - Presenter
 * - Controller
 */ ?>

<?php // @section: Model

// Uniter imports
// See https://www.npmjs.com/package/modelar
// Serves as an example...there are better libs that'd need
// a proper Uniter port. I.e.: TypeORM, Knex, etc.
import "Model" as class Model from "modelar";

// If modelar had a Uniter port...
use modelar\Model;

class Article extends Model {

    // Assuming a Uniter port had been done, this'd be possible.
    private $__table = "articles";
    private $__primary = "id";
    private $__fields = ["id", "title", "content"];
    private $__searchable = ["title", "content"];

    public function __construct(array $data = []) {
        // Do something to the data, before forwarding it.
        parent::__construct($data);
    }


    // Add verifiers, setters, getters, ...

    // Implement traits as Mixins?
    // If so, we'd maybe have...
    using \IngwiePhoenix\UniterMPC\Models\Displayable;

    public function __toDOM() {
        return (<div>
            <p>We present to you:</p>
            <span class="title">{$this->title}</div>
        </div>);
    }
    public function __toJSONLD() {
        // ...
    }
    public function __toJSON() {
        // ...
    }

    // Check for a model to be viewable with something like:
    // if(in_array(class_uses(get_class($article)), \IngwiePhoenix\UniterMPC\Models\Displayable::class))
    // Alternatively, use interfaces...
} ?>

<?php // @section: Presenter
use \IngwiePhoenix\UniterMPC\IPresenter;
class Articles implements IPresenter {
    public function __construct($data) {
        // Do some internal storing and preparing here.
        $this->data = $data;
    }
    public function __toDOM() {
        // Use $this->data and create a DOM.
        // Basically, use a JSX-ish syntax to do so and just
        // return the DOM fragment that had been built.
        // A DOM interpreter will turn that into an actual HTML
        // output. Something that could be used here is caching, where
        // a presenter could mark itself as being cacheable OR it
        // could dynamicaly allocate cached states of itself and just skip
        // processing itself entirely. It may only do so for fragments within too.
        return (<div class="articles">
            // ...
        </div>);
    }

    // As above, there are functions for JSON, JSON-LD and alike.
    // A presenter should only take care of presenting, it should be
    // understood as an enhanced view that rather uses
    // a virtual DOM instead of a templating engine.
    // Later, in a post-processing middleware, this could
    // be incredibly useful to do post-transforms.
} ?>

<?php // @section: controller
use \IngwiePhoenix\UniterMPC\ControllerBase;

use ...\Model as ArticleModel;
use ...\Presenter as ArticlePresenter;

// Use aliases?
use App;

class Article extends ControllerBase {
    public function __construct() {
        // Do something upon controller instantiation.
        $this->autodetectActions([
            // Method => pattern...should be set by default.
            HTTP::GET => new RegEx("get_(.+)$")
        ]);
    }

    // Make it possible to auto-detect actions
    public function get_index() {
        $articles = ArticleModel::query()
                    ->viaCache()
                    ->findAll();

        // Generate a cache key based on some criteria.
        // Hey, who said you couldn't use stringified JSONs
        // as keys for Redis? xD
        // a possible KEY:
        // {"criteria_userid":0}::\IngwiePhoenix\UniterMPC\App\Presenter\Articles
        // ... User-based, server-side cache, period. :)
        $presenter = ArticlePresenter::checkCache([
            "criteria_userid" => App::getUser()->id;
        ]);

        if($model->hitCache())
            App::$req->setHeader("x-cache","hit");

        // Dead simple way of executing a view.
        // Basically:
        // - if Content-Type is set in request header, reply with the
        // requested type, if possible.
        // - If the requested Content-Type can not be served, serve the
        // prefered presentation (view). Default would be HTML.
        //
        // A post-processing middleware would check weather a DOM has
        // been returned or not, and insert that into a default view.
        // If PJAX is being used however, it would also skip that step and
        // just provide the new HTML, allowing smooth transistions without
        // having to reload.
        return $presenter($model);
    }

    // We may make this MPC aware of real-time stuff, in which case:
    using \IngwiePhoenix\UniterMPC\LiveObject;
    public function rpcConnect(RPCData $rpcReq) {
        // Set up connection stuff.
        // The way that RPC is working, if either via
        // WebSockets or JSON-RPC or something, should be
        // managed by other modules and a configuration.
        // Assuming that I had implemented SocketCluster's
        // methods, I might actually have this:
        $sc = $rpcReq->SocketClusterConnection;
        $this->publishSelf($sc);

        // A lambda?
        $sc->on("ping", function($input, $respond){
            \JS\console::log("Responding...");
            $respond("Thanks for your input: $input");
        });
    }

    // Registered as: "foo"
    // Basically, this is an automated machanism to:
    // $sc->on("foo", Closure::fromCallable([$this, "rpc_foo"]))
    // Actual signature:
    // @param[in] mixed $data: The data sent by the client.
    // @param[in] callable $respond(mixed $data): Respond to sender.
    // @param[in] \JS\Error $err: Error, if any.
    public function rpc_foo($data) {
        \JS\console::log($data);
    }
} ?>

<?php // @section: app
import "*" as function express from "express";

// Utilize the fact that Node can require JSON and anything that
// it had been configured for. Bundlers may also use this, as these
// instructions are actual require calls.
import $config from "./config.json";

// Get application class.
use \IngwiePhoenix\UniterMPC\App;
$app = new App($config);

// Register all the controllers and their stuff...
$app->register(
    /* Type */ App::Controller,
    ArticleController::class
);
$app->register(
    App::Module, // Allows for modules to be bundled and have a central registrar.
    SomeModuleClass::class
);

// Set up the routes and middlewares.
// I am going to throw in some things from JS here,
// because that actually makes things a little bit faster.
// Although its more verbose, it saves Uniter a bunch of
// transpiling - and a few VM instructions too.
$srv = express();
__JS__ <$srv> {
    import {vm} from "phpcore";
    var srv = vm.unwrap($srv); // Might be unwrapped already?

    import {bodyParser} from "connect-body-parser";
    srv.use(bodyParser());

    import someOtherCoolMiddleware from "...";
    srv.use(someOtherCoolMiddleware);
};

// Have a neat little handler to automate controller and route
// registration.
// A registrar (module) may immediately define a few things, like routes,
// when it is being registered.
$app->configureExpress($srv);
// Alternatives...
// - $app->configureHttp();

// We could add some other handlers here.
// Remember, pre AND post-processing middlewares
// are being put BEFORE anything else!
// They usually override res.send() and alike.
$srv->use("/", $app->callbacks->defaultController);
$srv->use("*", $app->callbacks->defaultErrorHandler);

// One more trick: HTTPS.
__JS__ <$srv> {
    import {createServer as createHttpsServer} from "https";
    var https = createHttpsServer(/* args */);
    https.on("request", $srv);

    // Voila! :) Uniter MPC gets executed on HTTPS too - flawlessly.
}; ?>
