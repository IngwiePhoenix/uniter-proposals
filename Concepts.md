# Concepts for Uniter
...and it's belonging sub-projects.

## Importing JavaScript stuff

Importing JavaScript into Uniter usually happens either from the executor's side by calling methods on the virtual maschine - as far as I remember. But how can a Uniter program import things by itself? For this feature, I am kinda stealing ES6's syntax and hack it a little. :)

```
// Basic import:
import $foo from "module";
// Results in "$foo" being the raw exports of "module"

// Typed import:
import function Foo from "module";

// Importing specific symbols.
// Now this is extremely useful when using ES6/7 Tree-Shaking, as proper import statements can be made that will result in the transpiler knowing which symbols are being used - and which not!
import {
  function Foo,
  class Bar,
  $baz
} from "module";
// Result:
/* es6 */ import {Foo, Bar, baz} from "module";

// Import symbols into other names
import {
  "foo" as function Bar,
  "default" as $module
} from "module";

// Maybe a vice-versa structured one for long imports?
import from "module" { ... };
```

## `__JS__` block
Sometimes it's just faster and/or required to use raw JavaScript.

```
$val = __JS__{
    return Number(5)*20;
};

__JS__{
    // This actually is a generated IIFE and has a `vm` parameter.
    vm.someMethod(...);
};

$foo = true;
__JS__ <$foo> {
    // See the rather awkward function syntax? That's on purpose. This is not actually a function,
    // and therefore has it's own specific syntax.
    // The IIFE is basically: function(vm, ...phpVars...)
    // where "phpVars" basically contains the PHP variable instances.
    $foo = $foo + 100;
};
echo $foo; // 101, as JS treats the bool(true) as "1" during additions.
```

Also, see C's `__ASM__`, that is where I got this idea from.

## XHP (XML in PHP)
Basically, do a JSX-like translation. Due to operator overlapping, we should check inline a paranthesis pair for this only - makes things a little bit easier, I'd guess...

```
// PHP in:
$var = (<div>Foo</div>);
// PHP out;
$var = xhp(\XHP\unaliasTagName("div"), "foo", [], []);
// ^ Signature: $element, $text, $attrs, $children
// \XHP\unaliasTagName($tName) is meant to find out, if a tag has been
// re-purposed or overridden by the user. If not, it uses the default stdElement class.
// Useful for frameworks that would extend <button> to automatically feature WAI-ARIA.

// PHP in:
$var = (<div>
    <p>Foo bar</div>
</div>);
// out:
$var = xhp("div", [], [
    xhp("p", "Foo bar", [], [])
]);
```

These function calls here are just an example. In fact, Uniter might just translate these immediately and return a special, internal object (i.e. `stdElement`) with the proper properties.
