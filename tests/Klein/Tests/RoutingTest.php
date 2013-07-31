<?php
/**
 * Klein (klein.php) - A lightning fast router for PHP
 *
 * @author      Chris O'Hara <cohara87@gmail.com>
 * @author      Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link        https://github.com/chriso/klein.php
 * @license     MIT
 */

namespace Klein\Tests;


use \Klein\Klein;

use \Klein\Tests\Mocks\HeadersEcho;
use \Klein\Tests\Mocks\HeadersSave;
use \Klein\Tests\Mocks\MockRequestFactory;
use \Klein\Request;
use \Klein\Response;
use \Klein\ServiceProvider;
use \Klein\App;
use \Klein\DataCollection\RouteCollection;

/**
 * RoutingTest
 * 
 * @uses AbstractKleinTest
 * @package Klein\Tests
 */
class RoutingTest extends AbstractKleinTest
{

    public function testBasic()
    {
        $this->expectOutputString('x');

        $this->klein_app->respond(
            '/',
            function () {
                echo 'x';
            }
        );
        $this->klein_app->respond(
            '/something',
            function () {
                echo 'y';
            }
        );

        $this->klein_app->dispatch(
            MockRequestFactory::create('/')
        );
    }

    public function testCallable()
    {
        $this->expectOutputString('okok');

        $this->klein_app->respond('/', array(__NAMESPACE__ . '\Mocks\TestClass', 'GET'));
        $this->klein_app->respond('/', __NAMESPACE__ . '\Mocks\TestClass::GET');

        $this->klein_app->dispatch(
            MockRequestFactory::create('/')
        );
    }

    public function testCallbackArguments()
    {
        // Create expected objects
        $expected_objects = array(
            'request'         => null,
            'response'        => null,
            'service'         => null,
            'app'             => null,
            'matched'         => null,
            'methods_matched' => null,
        );

        $this->klein_app->respond(
            function ($a, $b, $c, $d, $e, $f) use (&$expected_objects) {
                $expected_objects['request']         = $a;
                $expected_objects['response']        = $b;
                $expected_objects['service']         = $c;
                $expected_objects['app']             = $d;
                $expected_objects['matched']         = $e;
                $expected_objects['methods_matched'] = $f;
            }
        );

        $this->klein_app->dispatch();

        $this->assertTrue($expected_objects['request'] instanceof Request);
        $this->assertTrue($expected_objects['response'] instanceof Response);
        $this->assertTrue($expected_objects['service'] instanceof ServiceProvider);
        $this->assertTrue($expected_objects['app'] instanceof App);
        $this->assertTrue($expected_objects['matched'] instanceof RouteCollection);
        $this->assertTrue(is_array($expected_objects['methods_matched']));
    }

    public function testAppReference()
    {
        $this->expectOutputString('ab');

        $this->klein_app->respond(
            '/',
            function ($r, $r, $s, $a) {
                $a->state = 'a';
            }
        );
        $this->klein_app->respond(
            '/',
            function ($r, $r, $s, $a) {
                $a->state .= 'b';
            }
        );
        $this->klein_app->respond(
            '/',
            function ($r, $r, $s, $a) {
                print $a->state;
            }
        );

        $this->klein_app->dispatch(
            MockRequestFactory::create('/')
        );
    }

    public function testDispatchOutput()
    {
        $expectedOutput = array(
            'returned1' => 'alright!',
            'returned2' => 'woot!',
        );

        $this->klein_app->respond(
            function () use ($expectedOutput) {
                return $expectedOutput['returned1'];
            }
        );
        $this->klein_app->respond(
            function () use ($expectedOutput) {
                return $expectedOutput['returned2'];
            }
        );

        $this->klein_app->dispatch();

        // Expect our output to match our ECHO'd output
        $this->expectOutputString(
            $expectedOutput['returned1'] . $expectedOutput['returned2']
        );

        // Make sure our response body matches the concatenation of what we returned in each callback
        $this->assertSame(
            $expectedOutput['returned1'] . $expectedOutput['returned2'],
            $this->klein_app->response()->body()
        );
    }

    public function testDispatchOutputNotSent()
    {
        $this->klein_app->respond(
            function () {
                return 'test output';
            }
        );

        $this->klein_app->dispatch(null, null, false);

        $this->expectOutputString('');

        $this->assertSame(
            'test output',
            $this->klein_app->response()->body()
        );
    }

    public function testDispatchOutputCaptured()
    {
        $expectedOutput = array(
            'echoed' => 'yup',
            'returned' => 'nope',
        );

        $this->klein_app->respond(
            function () use ($expectedOutput) {
                echo $expectedOutput['echoed'];
            }
        );
        $this->klein_app->respond(
            function () use ($expectedOutput) {
                return $expectedOutput['returned'];
            }
        );

        $output = $this->klein_app->dispatch(null, null, true, Klein::DISPATCH_CAPTURE_AND_RETURN);

        // Make sure nothing actually printed to the screen
        $this->expectOutputString('');

        // Make sure our returned output matches what we ECHO'd
        $this->assertSame($expectedOutput['echoed'], $output);

        // Make sure our response body matches what we returned
        $this->assertSame($expectedOutput['returned'], $this->klein_app->response()->body());
    }

    public function testDispatchOutputReplaced()
    {
        $expectedOutput = array(
            'echoed' => 'yup',
            'returned' => 'nope',
        );

        $this->klein_app->respond(
            function () use ($expectedOutput) {
                echo $expectedOutput['echoed'];
            }
        );
        $this->klein_app->respond(
            function () use ($expectedOutput) {
                return $expectedOutput['returned'];
            }
        );

        $this->klein_app->dispatch(null, null, false, Klein::DISPATCH_CAPTURE_AND_REPLACE);

        // Make sure nothing actually printed to the screen
        $this->expectOutputString('');

        // Make sure our response body matches what we echoed
        $this->assertSame($expectedOutput['echoed'], $this->klein_app->response()->body());
    }

    public function testDispatchOutputPrepended()
    {
        $expectedOutput = array(
            'echoed' => 'yup',
            'returned' => 'nope',
            'echoed2' => 'sure',
        );

        $this->klein_app->respond(
            function () use ($expectedOutput) {
                echo $expectedOutput['echoed'];
            }
        );
        $this->klein_app->respond(
            function () use ($expectedOutput) {
                return $expectedOutput['returned'];
            }
        );
        $this->klein_app->respond(
            function () use ($expectedOutput) {
                echo $expectedOutput['echoed2'];
            }
        );

        $this->klein_app->dispatch(null, null, false, Klein::DISPATCH_CAPTURE_AND_PREPEND);

        // Make sure nothing actually printed to the screen
        $this->expectOutputString('');

        // Make sure our response body matches what we echoed
        $this->assertSame(
            $expectedOutput['echoed'] . $expectedOutput['echoed2'] . $expectedOutput['returned'],
            $this->klein_app->response()->body()
        );
    }

    public function testDispatchOutputAppended()
    {
        $expectedOutput = array(
            'echoed' => 'yup',
            'returned' => 'nope',
            'echoed2' => 'sure',
        );

        $this->klein_app->respond(
            function () use ($expectedOutput) {
                echo $expectedOutput['echoed'];
            }
        );
        $this->klein_app->respond(
            function () use ($expectedOutput) {
                return $expectedOutput['returned'];
            }
        );
        $this->klein_app->respond(
            function () use ($expectedOutput) {
                echo $expectedOutput['echoed2'];
            }
        );

        $this->klein_app->dispatch(null, null, false, Klein::DISPATCH_CAPTURE_AND_APPEND);

        // Make sure nothing actually printed to the screen
        $this->expectOutputString('');

        // Make sure our response body matches what we echoed
        $this->assertSame(
            $expectedOutput['returned'] . $expectedOutput['echoed'] . $expectedOutput['echoed2'],
            $this->klein_app->response()->body()
        );
    }

    public function testDispatchResponseReplaced()
    {
        $expected_body = 'You SHOULD see this';
        $expected_code = 201;

        $expected_append = 'This should be appended?';

        $this->klein_app->respond(
            '/',
            function ($request, $response) {
                // Set our response code
                $response->code(569);

                return 'This should disappear';
            }
        );
        $this->klein_app->respond(
            '/',
            function () use ($expected_body, $expected_code) {
                return new Response($expected_body, $expected_code);
            }
        );
        $this->klein_app->respond(
            '/',
            function () use ($expected_append) {
                return $expected_append;
            }
        );

        $this->klein_app->dispatch(null, null, false, Klein::DISPATCH_CAPTURE_AND_RETURN);

        // Make sure our response body and code match up
        $this->assertSame(
            $expected_body . $expected_append,
            $this->klein_app->response()->body()
        );
        $this->assertSame(
            $expected_code,
            $this->klein_app->response()->code()
        );
    }

    public function testRespondReturn()
    {
        $return_one = $this->klein_app->respond(
            function () {
                return 1337;
            }
        );
        $return_two = $this->klein_app->respond(
            function () {
                return 'dog';
            }
        );

        $this->klein_app->dispatch(null, null, false);

        $this->assertTrue(is_callable($return_one));
        $this->assertTrue(is_callable($return_two));
    }

    public function testRespondReturnChaining()
    {
        $return_one = $this->klein_app->respond(
            function () {
                return 1337;
            }
        );
        $return_two = $this->klein_app->respond(
            function () {
                return 1337;
            }
        )->getPath();

        $this->assertSame($return_one->getPath(), $return_two);
    }

    public function testCatchallImplicit()
    {
        $this->expectOutputString('b');

        $this->klein_app->respond(
            '/one',
            function () {
                echo 'a';
            }
        );
        $this->klein_app->respond(
            function () {
                echo 'b';
            }
        );
        $this->klein_app->respond(
            '/two',
            function () {

            }
        );
        $this->klein_app->respond(
            '/three',
            function () {
                echo 'c';
            }
        );

        $this->klein_app->dispatch(
            MockRequestFactory::create('/two')
        );
    }

    public function testCatchallAsterisk()
    {
        $this->expectOutputString('b');

        $this->klein_app->respond(
            '/one',
            function () {
                echo 'a';
            }
        );
        $this->klein_app->respond(
            '*',
            function () {
                echo 'b';
            }
        );
        $this->klein_app->respond(
            '/two',
            function () {

            }
        );
        $this->klein_app->respond(
            '/three',
            function () {
                echo 'c';
            }
        );

        $this->klein_app->dispatch(
            MockRequestFactory::create('/two')
        );
    }

    public function testCatchallImplicitTriggers404()
    {
        $this->expectOutputString("b404\n");

        $this->klein_app->respond(
            function () {
                echo 'b';
            }
        );
        $this->klein_app->respond(
            404,
            function () {
                echo "404\n";
            }
        );

        $this->klein_app->dispatch(
            MockRequestFactory::create('/')
        );
    }

    public function testRegex()
    {
        $this->expectOutputString('z');

        $this->klein_app->respond(
            '@/bar',
            function () {
                echo 'z';
            }
        );

        $this->klein_app->dispatch(
            MockRequestFactory::create('/bar')
        );
    }

    public function testRegexNegate()
    {
        $this->expectOutputString("y");

        $this->klein_app->respond(
            '!@/foo',
            function () {
                echo 'y';
            }
        );

        $this->klein_app->dispatch(
            MockRequestFactory::create('/bar')
        );
    }

    public function test404()
    {
        $this->expectOutputString("404\n");

        $this->klein_app->respond(
            '/',
            function () {
                echo 'a';
            }
        );
        $this->klein_app->respond(
            404,
            function () {
                echo "404\n";
            }
        );

        $this->klein_app->dispatch(
            MockRequestFactory::create('/foo')
        );

        $this->assertSame(404, $this->klein_app->response()->code());
    }

    public function testParamsBasic()
    {
        $this->expectOutputString('blue');

        $this->klein_app->respond(
            '/[:color]',
            function ($request) {
                echo $request->param('color');
            }
        );

        $this->klein_app->dispatch(
            MockRequestFactory::create('/blue')
        );
    }

    public function testParamsIntegerSuccess()
    {
        $this->expectOutputString("string(3) \"987\"\n");

        $this->klein_app->respond(
            '/[i:age]',
            function ($request) {
                var_dump($request->param('age'));
            }
        );

        $this->klein_app->dispatch(
            MockRequestFactory::create('/987')
        );
    }

    public function testParamsIntegerFail()
    {
        $this->expectOutputString('404 Code');

        $this->klein_app->respond(
            '/[i:age]',
            function ($request) {
                var_dump($request->param('age'));
            }
        );
        $this->klein_app->respond(
            '404',
            function () {
                echo '404 Code';
            }
        );

        $this->klein_app->dispatch(
            MockRequestFactory::create('/blue')
        );
    }

    public function testParamsAlphaNum()
    {
        $this->klein_app->respond(
            '/[a:audible]',
            function ($request) {
                echo $request->param('audible');
            }
        );


        $this->assertSame(
            'blue42',
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create('/blue42')
            )
        );
        $this->assertSame(
            '',
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create('/texas-29')
            )
        );
        $this->assertSame(
            '',
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create('/texas29!')
            )
        );
    }

    public function testParamsHex()
    {
        $this->klein_app->respond(
            '/[h:hexcolor]',
            function ($request) {
                echo $request->param('hexcolor');
            }
        );


        $this->assertSame(
            '00f',
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create('/00f')
            )
        );
        $this->assertSame(
            'abc123',
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create('/abc123')
            )
        );
        $this->assertSame(
            '',
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create('/876zih')
            )
        );
        $this->assertSame(
            '',
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create('/00g')
            )
        );
        $this->assertSame(
            '',
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create('/hi23')
            )
        );
    }

    public function testParamsSlug()
    {
        $this->klein_app->respond(
            '/[s:slug_name]',
            function ($request) {
                echo $request->param('slug_name');
            }
        );


        $this->assertSame(
            'dog-thing',
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create('/dog-thing')
            )
        );
        $this->assertSame(
            'a_badass_slug',
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create('/a_badass_slug')
            )
        );
        $this->assertSame(
            'AN_UPERCASE_SLUG',
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create('/AN_UPERCASE_SLUG')
            )
        );
        $this->assertSame(
            'sample-wordpress-like-post-slug-based-on-the-title-2013-edition',
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create('/sample-wordpress-like-post-slug-based-on-the-title-2013-edition')
            )
        );
        $this->assertSame(
            '',
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create('/%!@#')
            )
        );
        $this->assertSame(
            '',
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create('/')
            )
        );
        $this->assertSame(
            '',
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create('/dog-%thing')
            )
        );
    }

    public function testPathParamsAreUrlDecoded()
    {
        $this->klein_app->respond(
            '/[:test]',
            function ($request) {
                echo $request->param('test');
            }
        );

        $this->assertSame(
            'Knife Party',
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create('/Knife%20Party')
            )
        );

        $this->assertSame(
            'and/or',
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create('/and%2For')
            )
        );
    }

    public function testPathParamsAreUrlDecodedToRFC3986Spec()
    {
        $this->klein_app->respond(
            '/[:test]',
            function ($request) {
                echo $request->param('test');
            }
        );

        $this->assertNotSame(
            'Knife Party',
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create('/Knife+Party')
            )
        );

        $this->assertSame(
            'Knife+Party',
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create('/Knife+Party')
            )
        );
    }

    public function test404TriggersOnce()
    {
        $this->expectOutputString('d404 Code');

        $this->klein_app->respond(
            function () {
                echo "d";
            }
        );
        $this->klein_app->respond(
            '404',
            function () {
                echo '404 Code';
            }
        );

        $this->klein_app->dispatch(
            MockRequestFactory::create('/notroute')
        );
    }

    public function testMethodCatchAll()
    {
        $this->expectOutputString('yup!123');

        $this->klein_app->respond(
            'POST',
            null,
            function ($request) {
                echo 'yup!';
            }
        );
        $this->klein_app->respond(
            'POST',
            '*',
            function ($request) {
                echo '1';
            }
        );
        $this->klein_app->respond(
            'POST',
            '/',
            function ($request) {
                echo '2';
            }
        );
        $this->klein_app->respond(
            function ($request) {
                echo '3';
            }
        );

        $this->klein_app->dispatch(
            MockRequestFactory::create('/', 'POST')
        );
    }

    public function testLazyTrailingMatch()
    {
        $this->expectOutputString('this-is-a-title-123');

        $this->klein_app->respond(
            '/posts/[*:title][i:id]',
            function ($request) {
                echo $request->param('title')
                . $request->param('id');
            }
        );

        $this->klein_app->dispatch(
            MockRequestFactory::create('/posts/this-is-a-title-123')
        );
    }

    public function testFormatMatch()
    {
        $this->expectOutputString('xml');

        $this->klein_app->respond(
            '/output.[xml|json:format]',
            function ($request) {
                echo $request->param('format');
            }
        );

        $this->klein_app->dispatch(
            MockRequestFactory::create('/output.xml')
        );
    }

    public function testDotSeparator()
    {
        $this->expectOutputString('matchA:slug=ABCD_E--matchB:slug=ABCD_E--');

        $this->klein_app->respond(
            '/[*:cpath]/[:slug].[:format]',
            function ($rq) {
                echo 'matchA:slug='.$rq->param("slug").'--';
            }
        );
        $this->klein_app->respond(
            '/[*:cpath]/[:slug].[:format]?',
            function ($rq) {
                echo 'matchB:slug='.$rq->param("slug").'--';
            }
        );
        $this->klein_app->respond(
            '/[*:cpath]/[a:slug].[:format]?',
            function ($rq) {
                echo 'matchC:slug='.$rq->param("slug").'--';
            }
        );

        $this->klein_app->dispatch(
            MockRequestFactory::create("/category1/categoryX/ABCD_E.php")
        );

        $this->assertSame(
            'matchA:slug=ABCD_E--matchB:slug=ABCD_E--',
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create('/category1/categoryX/ABCD_E.php')
            )
        );
        $this->assertSame(
            'matchB:slug=ABCD_E--',
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create('/category1/categoryX/ABCD_E')
            )
        );
    }

    public function testControllerActionStyleRouteMatch()
    {
        $this->expectOutputString('donkey-kick');

        $this->klein_app->respond(
            '/[:controller]?/[:action]?',
            function ($request) {
                echo $request->param('controller')
                     . '-' . $request->param('action');
            }
        );

        $this->klein_app->dispatch(
            MockRequestFactory::create('/donkey/kick')
        );
    }

    public function testRespondArgumentOrder()
    {
        $this->expectOutputString('abcdef');

        $this->klein_app->respond(
            function () {
                echo 'a';
            }
        );
        $this->klein_app->respond(
            null,
            function () {
                echo 'b';
            }
        );
        $this->klein_app->respond(
            '/endpoint',
            function () {
                echo 'c';
            }
        );
        $this->klein_app->respond(
            'GET',
            null,
            function () {
                echo 'd';
            }
        );
        $this->klein_app->respond(
            array('GET', 'POST'),
            null,
            function () {
                echo 'e';
            }
        );
        $this->klein_app->respond(
            array('GET', 'POST'),
            '/endpoint',
            function () {
                echo 'f';
            }
        );

        $this->klein_app->dispatch(
            MockRequestFactory::create('/endpoint')
        );
    }

    public function testTrailingMatch()
    {
        $this->klein_app->respond(
            '/?[*:trailing]/dog/?',
            function ($request) {
                echo 'yup';
            }
        );


        $this->assertSame(
            'yup',
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create('/cat/dog')
            )
        );
        $this->assertSame(
            'yup',
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create('/cat/cheese/dog')
            )
        );
        $this->assertSame(
            'yup',
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create('/cat/ball/cheese/dog/')
            )
        );
        $this->assertSame(
            'yup',
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create('/cat/ball/cheese/dog')
            )
        );
        $this->assertSame(
            'yup',
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create('cat/ball/cheese/dog/')
            )
        );
        $this->assertSame(
            'yup',
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create('cat/ball/cheese/dog')
            )
        );
    }

    public function testTrailingPossessiveMatch()
    {
        $this->klein_app->respond(
            '/sub-dir/[**:trailing]',
            function ($request) {
                echo 'yup';
            }
        );


        $this->assertSame(
            'yup',
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create('/sub-dir/dog')
            )
        );

        $this->assertSame(
            'yup',
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create('/sub-dir/cheese/dog')
            )
        );

        $this->assertSame(
            'yup',
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create('/sub-dir/ball/cheese/dog/')
            )
        );

        $this->assertSame(
            'yup',
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create('/sub-dir/ball/cheese/dog')
            )
        );
    }

    public function testNSDispatch()
    {
        // Create a duplicate reference... yea, PHP 5.3 :/
        $klein_app = $this->klein_app;

        $this->klein_app->with(
            '/u',
            function () use ($klein_app) {
                $klein_app->respond(
                    'GET',
                    '/?',
                    function ($request, $response) {
                        echo "slash";
                    }
                );
                $klein_app->respond(
                    'GET',
                    '/[:id]',
                    function ($request, $response) {
                        echo "id";
                    }
                );
            }
        );
        $this->klein_app->respond(
            404,
            function ($request, $response) {
                echo "404";
            }
        );


        $this->assertSame(
            "slash",
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create("/u")
            )
        );
        $this->assertSame(
            "slash",
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create("/u/")
            )
        );
        $this->assertSame(
            "id",
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create("/u/35")
            )
        );
        $this->assertSame(
            "404",
            $this->dispatchAndReturnOutput(
                MockRequestFactory::create("/35")
            )
        );
    }

    public function testNSDispatchExternal()
    {
        $ext_namespaces = $this->loadExternalRoutes();

        $this->klein_app->respond(
            404,
            function ($request, $response) {
                echo "404";
            }
        );

        foreach ($ext_namespaces as $namespace) {

            $this->assertSame(
                'yup',
                $this->dispatchAndReturnOutput(
                    MockRequestFactory::create($namespace . '/')
                )
            );

            $this->assertSame(
                'yup',
                $this->dispatchAndReturnOutput(
                    MockRequestFactory::create($namespace . '/testing/')
                )
            );
        }
    }

    public function testNSDispatchExternalRerequired()
    {
        $ext_namespaces = $this->loadExternalRoutes();

        $this->klein_app->respond(
            404,
            function ($request, $response) {
                echo "404";
            }
        );

        foreach ($ext_namespaces as $namespace) {

            $this->assertSame(
                'yup',
                $this->dispatchAndReturnOutput(
                    MockRequestFactory::create($namespace . '/')
                )
            );

            $this->assertSame(
                'yup',
                $this->dispatchAndReturnOutput(
                    MockRequestFactory::create($namespace . '/testing/')
                )
            );
        }
    }

    public function test405DefaultRequest()
    {
        $this->klein_app->respond(
            array('GET', 'POST'),
            null,
            function () {
                echo 'fail';
            }
        );

        $this->klein_app->dispatch(
            MockRequestFactory::create('/', 'DELETE')
        );

        $this->assertEquals('405 Method Not Allowed', $this->klein_app->response()->status()->getFormattedString());
        $this->assertEquals('GET, POST', $this->klein_app->response()->headers()->get('Allow'));
    }

    public function test405Routes()
    {
        $resultArray = array();

        $this->expectOutputString('_');

        $this->klein_app->respond(
            function () {
                echo '_';
            }
        );
        $this->klein_app->respond(
            'GET',
            null,
            function () {
                echo 'fail';
            }
        );
        $this->klein_app->respond(
            array('GET', 'POST'),
            null,
            function () {
                echo 'fail';
            }
        );
        $this->klein_app->respond(
            405,
            function ($a, $b, $c, $d, $e, $methods) use (&$resultArray) {
                $resultArray = $methods;
            }
        );

        $this->klein_app->dispatch(
            MockRequestFactory::create('/sure', 'DELETE')
        );

        $this->assertCount(2, $resultArray);
        $this->assertContains('GET', $resultArray);
        $this->assertContains('POST', $resultArray);
        $this->assertSame(405, $this->klein_app->response()->code());
    }

    public function testOptionsDefaultRequest()
    {
        $this->klein_app->respond(
            function ($request, $response) {
                $response->code(200);
            }
        );
        $this->klein_app->respond(
            array('GET', 'POST'),
            null,
            function () {
                echo 'fail';
            }
        );

        $this->klein_app->dispatch(
            MockRequestFactory::create('/', 'OPTIONS')
        );

        $this->assertEquals('200 OK', $this->klein_app->response()->status()->getFormattedString());
        $this->assertEquals('GET, POST', $this->klein_app->response()->headers()->get('Allow'));
    }

    public function testOptionsRoutes()
    {
        $access_control_headers = array(
            array(
                'key' => 'Access-Control-Allow-Origin',
                'val' => 'http://example.com',
            ),
            array(
                'key' => 'Access-Control-Allow-Methods',
                'val' => 'POST, GET, DELETE, OPTIONS, HEAD',
            ),
        );

        $this->klein_app->respond(
            'GET',
            null,
            function () {
                echo 'fail';
            }
        );
        $this->klein_app->respond(
            array('GET', 'POST'),
            null,
            function () {
                echo 'fail';
            }
        );
        $this->klein_app->respond(
            'OPTIONS',
            null,
            function ($request, $response) use ($access_control_headers) {
                // Add access control headers
                foreach ($access_control_headers as $header) {
                    $response->header($header[ 'key' ], $header[ 'val' ]);
                }
            }
        );

        $this->klein_app->dispatch(
            MockRequestFactory::create('/', 'OPTIONS')
        );


        // Assert headers were passed
        $this->assertEquals('GET, POST, OPTIONS', $this->klein_app->response()->headers()->get('Allow'));

        foreach ($access_control_headers as $header) {
            $this->assertEquals($header['val'], $this->klein_app->response()->headers()->get($header['key']));
        }
    }

    public function testHeadDefaultRequest()
    {
        $expected_headers = array(
            array(
                'key' => 'X-Some-Random-Header',
                'val' => 'This was a GET route',
            ),
        );

        $this->klein_app->respond(
            'GET',
            null,
            function ($request, $response) use ($expected_headers) {
                $response->code(200);

                // Add access control headers
                foreach ($expected_headers as $header) {
                    $response->header($header[ 'key' ], $header[ 'val' ]);
                }
            }
        );
        $this->klein_app->respond(
            'GET',
            '/',
            function () {
                echo 'GET!';
                return 'more text';
            }
        );
        $this->klein_app->respond(
            'POST',
            '/',
            function () {
                echo 'POST!';
            }
        );

        $this->klein_app->dispatch(
            MockRequestFactory::create('/', 'HEAD')
        );

        // Make sure we don't get a response body
        $this->expectOutputString('');

        // Assert headers were passed
        foreach ($expected_headers as $header) {
            $this->assertEquals($header['val'], $this->klein_app->response()->headers()->get($header['key']));
        }
    }

    public function testGetPathFor()
    {
        $this->klein_app->respond(
            '/dogs',
            function () {
            }
        )->setName('dogs');

        $this->klein_app->respond(
            '/dogs/[i:dog_id]/collars',
            function () {
            }
        )->setName('dog-collars');

        $this->klein_app->respond(
            '/dogs/[i:dog_id]/collars/[a:collar_slug]/?',
            function () {
            }
        )->setName('dog-collar-details');

        $this->klein_app->respond(
            '/dog/foo',
            function () {
            }
        )->setName('dog-foo');

        $this->klein_app->respond(
            '@/dog/regex',
            function () {
            }
        )->setName('dog-regex');

        $this->klein_app->respond(
            '!@/dog/regex',
            function () {
            }
        )->setName('dog-neg-regex');

        $this->klein_app->respond(
            '@\.(json|csv)$',
            function () {
            }
        )->setName('complex-regex');

        $this->klein_app->respond(
            '!@^/admin/',
            function () {
            }
        )->setName('complex-neg-regex');

        $this->klein_app->dispatch(
            MockRequestFactory::create('/', 'HEAD')
        );

        $this->assertSame(
            '/dogs',
            $this->klein_app->getPathFor('dogs')
        );
        $this->assertSame(
            '/dogs/[i:dog_id]/collars',
            $this->klein_app->getPathFor('dog-collars')
        );
        $this->assertSame(
            '/dogs/idnumberandstuff/collars',
            $this->klein_app->getPathFor(
                'dog-collars',
                array(
                    'dog_id' => 'idnumberandstuff',
                )
            )
        );
        $this->assertSame(
            '/dogs/[i:dog_id]/collars/[a:collar_slug]/?',
            $this->klein_app->getPathFor('dog-collar-details')
        );
        $this->assertSame(
            '/dogs/idnumberandstuff/collars/d12f3d1f2d3/?',
            $this->klein_app->getPathFor(
                'dog-collar-details',
                array(
                    'dog_id' => 'idnumberandstuff',
                    'collar_slug' => 'd12f3d1f2d3',
                )
            )
        );
        $this->assertSame(
            '/dog/foo',
            $this->klein_app->getPathFor('dog-foo')
        );
        $this->assertSame(
            '/',
            $this->klein_app->getPathFor('dog-regex')
        );
        $this->assertSame(
            '/',
            $this->klein_app->getPathFor('dog-neg-regex')
        );
        $this->assertSame(
            '@/dog/regex',
            $this->klein_app->getPathFor('dog-regex', null, false)
        );
        $this->assertNotSame(
            '/',
            $this->klein_app->getPathFor('dog-neg-regex', null, false)
        );
        $this->assertSame(
            '/',
            $this->klein_app->getPathFor('complex-regex')
        );
        $this->assertSame(
            '/',
            $this->klein_app->getPathFor('complex-neg-regex')
        );
        $this->assertSame(
            '@\.(json|csv)$',
            $this->klein_app->getPathFor('complex-regex', null, false)
        );
        $this->assertNotSame(
            '/',
            $this->klein_app->getPathFor('complex-neg-regex', null, false)
        );
    }

    public function testDispatchHalt()
    {
        $this->expectOutputString('2,4,7,8,');

        // Create a duplicate reference... yea, PHP 5.3 :/
        $klein_app = $this->klein_app;

        $this->klein_app->respond(
            function () use ($klein_app) {
                $klein_app->skipThis();
                echo '1,';
            }
        );
        $this->klein_app->respond(
            function () use ($klein_app) {
                echo '2,';
                $klein_app->skipNext();
            }
        );
        $this->klein_app->respond(
            function () use ($klein_app) {
                echo '3,';
            }
        );
        $this->klein_app->respond(
            function () use ($klein_app) {
                echo '4,';
                $klein_app->skipNext(2);
            }
        );
        $this->klein_app->respond(
            function () use ($klein_app) {
                echo '5,';
            }
        );
        $this->klein_app->respond(
            function () use ($klein_app) {
                echo '6,';
            }
        );
        $this->klein_app->respond(
            function () use ($klein_app) {
                echo '7,';
            }
        );
        $this->klein_app->respond(
            function () use ($klein_app) {
                echo '8,';
                $klein_app->skipRemaining();
            }
        );
        $this->klein_app->respond(
            function () use ($klein_app) {
                echo '9,';
            }
        );
        $this->klein_app->respond(
            function () use ($klein_app) {
                echo '10,';
            }
        );

        $this->klein_app->dispatch();
    }

    public function testDispatchSkipCauses404()
    {
        $this->expectOutputString('404');

        // Create a duplicate reference... yea, PHP 5.3 :/
        $klein_app = $this->klein_app;

        $this->klein_app->respond(
            'POST',
            '/steez',
            function () use ($klein_app) {
                $klein_app->skipThis();
                echo 'Style... with ease';
            }
        );
        $this->klein_app->respond(
            'GET',
            '/nope',
            function () use ($klein_app) {
                echo 'How did I get here?!';
            }
        );
        $this->klein_app->respond(
            '404',
            function () use ($klein_app) {
                echo '404';
            }
        );

        $this->klein_app->dispatch(
            MockRequestFactory::create('/steez', 'POST')
        );
    }

    public function testDispatchAbort()
    {
        $this->expectOutputString('1,');

        // Create a duplicate reference... yea, PHP 5.3 :/
        $klein_app = $this->klein_app;

        $this->klein_app->respond(
            function () use ($klein_app) {
                echo '1,';
            }
        );
        $this->klein_app->respond(
            function () use ($klein_app) {
                $klein_app->abort(404);
                echo '2,';
            }
        );
        $this->klein_app->respond(
            function () use ($klein_app) {
                echo '3,';
            }
        );

        $this->klein_app->dispatch();

        $this->assertSame(404, $this->klein_app->response()->code());
    }

    public function testGetAlias()
    {
        $this->expectOutputString('1,2,');

        // With path
        $this->klein_app->get(
            '/',
            function () {
                echo '1,';
            }
        );

        // Without path
        $this->klein_app->get(
            function () {
                echo '2,';
            }
        );

        $this->klein_app->dispatch(
            MockRequestFactory::create('/')
        );
    }

    public function testPostAlias()
    {
        $this->expectOutputString('1,2,');

        // With path
        $this->klein_app->post(
            '/',
            function () {
                echo '1,';
            }
        );

        // Without path
        $this->klein_app->post(
            function () {
                echo '2,';
            }
        );

        $this->klein_app->dispatch(
            MockRequestFactory::create('/', 'POST')
        );
    }

    public function testPutAlias()
    {
        $this->expectOutputString('1,2,');

        // With path
        $this->klein_app->put(
            '/',
            function () {
                echo '1,';
            }
        );

        // Without path
        $this->klein_app->put(
            function () {
                echo '2,';
            }
        );

        $this->klein_app->dispatch(
            MockRequestFactory::create('/', 'PUT')
        );
    }

    public function testDeleteAlias()
    {
        $this->expectOutputString('1,2,');

        // With path
        $this->klein_app->delete(
            '/',
            function () {
                echo '1,';
            }
        );

        // Without path
        $this->klein_app->delete(
            function () {
                echo '2,';
            }
        );

        $this->klein_app->dispatch(
            MockRequestFactory::create('/', 'DELETE')
        );
    }
}
