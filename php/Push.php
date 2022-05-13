<?php

// Remains from a test - we rather should move this into a different extension


function debugLog ($text) {
  if($tmpFile = fopen( "extensions/Parsifal/log/PushLog", 'a')) {fwrite($tmpFile, $text);  fclose($tmpFile);} 
  else {throw new Exception ("debugLog in Push could not log"); }
}


debugLog ("Hallo Push");


// PARTS from the mediawiki push extension

abstract class ApiPushBase extends ApiBase {

protected $cookieJars = [];                          // Associative array containing CookieJar objects (values) to be passed in order to authenticate to the targets (keys).

// public function needsToken() {return 'csrf';}  // we are not changing our wiki, so we need no token 

// Log in into target wiki using the provided username and password.
protected function doLogin( $user, $password, $domain, $target, $token = null, $cookieJar = null, $attemtNr = 0 ) {  debugLog ("ApiPushBase: doLogin\n");
  $requestData = ['action' => 'login', 'format' => 'json', 'lgname' => $user, 'lgpassword' => $password, ];
  if ( $domain != false ) { $requestData['lgdomain'] = $domain; }
  if ( $token !== null ) { $requestData['lgtoken'] = $token; }

  $req = MWHttpRequest::factory( $target, [ 'postData' => $requestData, 'method' => 'POST', 'timeout' => 'default' ], __METHOD__);
  if ( $cookieJar !== null ) {$req->setCookieJar( $cookieJar );}
  $status = $req->execute();
  $attemtNr++;

  if ( !$status->isOK() ) { $this->dieWithError( wfMessage( 'push-err-authentication', $target, '' )->parse(), 'authentication-failed'); }
  $response = FormatJson::decode( $req->getContent() );
  
  if ( !property_exists( $response, 'login' ) || !property_exists( $response->login, 'result' ) ) {
    $this->dieWithError( wfMessage( 'push-err-authentication', $target, '' )->parse(), 'authentication-failed' );
  }

  if ( $response->login->result == 'NeedToken' && $attemtNr < 3 ) {
    $loginToken = $response->login->token ?? $this->getToken( $target, 'login' );
    $this->doLogin($user, $password, $domain, $target, $loginToken, $req->getCookieJar(), $attemtNr );} 
  elseif ( $response->login->result == 'Success' ) {$this->cookieJars[$target] = $req->getCookieJar();} 
  else {$this->dieWithError( wfMessage( 'push-err-authentication', $target, '' )->parse(), 'authentication-failed');}
}


// Obtains the needed token by making an HTTP GET request
protected function getToken( string $target, string $type ) {   debugLog ("ApiPushBase: getToken\n");
  $requestData = [ 'action' => 'query', 'format' => 'json', 'meta' => 'tokens', 'type' => $type,];

  $req = MWHttpRequest::factory( wfAppendQuery( $target, $requestData ), [ 'method' => 'GET', 'timeout' => 'default' ], __METHOD__ );

  if ( array_key_exists( $target, $this->cookieJars ) ) { $req->setCookieJar( $this->cookieJars[$target] ); }
  $status = $req->execute();
  $response = $status->isOK() ? FormatJson::decode( $req->getContent() ) : null;
  $token = false;
  $tokenKey = $type . 'token';
  if ( $response === null|| !property_exists( $response, 'query' )	|| !property_exists( $response->query, 'tokens' ) || !property_exists( $response->query->tokens, $tokenKey ) ) {
    $this->dieWithError(wfMessage( 'push-special-err-token-failed' )->text(), 'token-request-failed' ); }

  if ( property_exists( $response->query->tokens, $tokenKey ) )  {$token = $response->query->tokens->{$tokenKey};} 
  elseif ($response !== null	&& property_exists( $response, 'query' ) && property_exists( $response->query, 'error' ) ) {$this->dieWithError( $response->query->error->message, 'token-request-failed' );} 
  else {$this->dieWithError(wfMessage( 'push-special-err-token-failed' )->text(), 'token-request-failed' );}
  return $token;
}




public function execute() { debugLog ("ApiPushBase: execute\n");
  $pushConfig         = new GlobalVarConfig( 'egPush' );
  $pushLoginUser      = $pushConfig->get( 'LoginUser' );
  $pushLoginPass      = $pushConfig->get( 'LoginPass' );
  $pushLoginUsers     = $pushConfig->get( 'LoginUsers' );
  $pushLoginPasswords = $pushConfig->get( 'LoginPasswords' );
  $pushLoginDomain    = $pushConfig->get( 'LoginDomain' );
  $pushLoginDomains   = $pushConfig->get( 'LoginDomains' );
  $pushTargets        = $pushConfig->get( 'Targets' );

  debugLog ("ApiPushBase: execute: haveconfig\n");

  $this->checkUserRightsAny( 'push' );
    debugLog ("ApiPushBase: checked \n");
  $block = $this->getUser()->getBlock();
  
  debugLog ("ApiPushBase: execute: block: ".print_r ($block, true)."\n");
  
  if ( $block ) {$this->dieBlocked( $block );}

  $params = $this->extractRequestParams();

  PushFunctions::flipKeys( $pushLoginUsers,     'users' );
  PushFunctions::flipKeys( $pushLoginPasswords, 'passwds' );
  PushFunctions::flipKeys( $pushLoginDomains,   'domains' );

  $targetsForProcessing = [];
  foreach ( $params['targets'] as &$target ) {
    if ( !in_array( $target, $pushTargets ) ) {continue;} // We have to process defined targets only for security reasons
    $user = false; $pass = false; $domain = false;
    
    if (array_key_exists( $target, $pushLoginUsers ) && array_key_exists( $target, $pushLoginPasswords ))  {$user = $pushLoginUsers[$target]; $pass = $pushLoginPasswords[$target];} 
    elseif ( $pushLoginUser !== '' && $pushLoginPass !== '' ) {$user = $pushLoginUser; $pass = $pushLoginPass;}
  	if ( array_key_exists( $target, $pushLoginDomains ) ) {$domain = $pushLoginDomains[$target];} 
    elseif ( $pushLoginDomain !== '' ) { $domain = $pushLoginDomain; }

    if ( substr( $target, -1 ) !== '/' ) {$target .= '/';}
    $target .= 'api.php';

    if ( $user !== false ) {$this->doLogin( $user, $pass, $domain, $target );}
    $targetsForProcessing[] = $target;
  }

  $this->doModuleExecute( $targetsForProcessing );
}


// array $targetsForProcessing We have to process defined targets only for security reasons
abstract protected function doModuleExecute( array $targetsForProcessing ); 

}







class ApiPush extends ApiPushBase {

protected $editResponses = [];
public function __construct( $main, $action ) {  debugLog ("will construct ApiPush\n"); 
  parent::__construct( $main, $action );
  debugLog ("did construct ApiPush\n"); 
}

// $targetsForProcessing We have to process defined targets only for security reasons
public function doModuleExecute( array $targetsForProcessing ) {  debugLog ("ApiPush:doModuleExecute\n");
  $params = $this->extractRequestParams();
  foreach ( $params['page'] as $page ) {
    $title = Title::newFromText( $page );
    $revision = $this->getPageRevision( $title );
    if ( $revision !== false ) {$this->doPush( $title, $revision, $targetsForProcessing ); }
  }
  foreach ( $this->editResponses as $response ) {$this->getResult()->addValue(null, null, FormatJson::decode( $response ) ); }
}



//  Makes an internal request to the API to get the needed revision.  return array or false
protected function getPageRevision( Title $title ) {  debugLog ("ApiPush:getPagerevision\n");
  $revId = PushFunctions::getRevisionToPush( $title );
  $requestData = ['action' => 'query', 'format' => 'json', 'prop' => 'revisions', 'rvprop' => 'timestamp|user|comment|content', 'titles' => $title->getFullText(), 'rvstartid' => $revId, 'rvendid' => $revId,];

  $api = new ApiMain( new FauxRequest( $requestData, true ), true );
  $api->execute();
  if ( defined( 'ApiResult::META_CONTENT' ) ) {
    $response = $api->getResult()->getResultData( null, [ 'BC' => [], 'Types' => [], 'Strip' => 'all', ] );} 
  else { $response = $api->getResultData(); }

  $revision = false;

  if ( $response !== false && array_key_exists( 'query', $response ) && array_key_exists( 'pages', $response['query'] )
			&& count( $response['query']['pages'] ) > 0
		) {

			foreach ( $response['query']['pages'] as $key => $value ) {
				$first = $key;
				break;
			}

			if ( array_key_exists( 'revisions', $response['query']['pages'][$first] )
				&& count( $response['query']['pages'][$first]['revisions'] ) > 0 ) {
				$revision = $response['query']['pages'][$first]['revisions'][0];
			} else {
				$this->dieWithError( wfMessage( 'push-special-err-pageget-failed' )->text(), 'page-get-failed' );
			}
		} else {
			$this->dieWithError( wfMessage( 'push-special-err-pageget-failed' )->text(), 'page-get-failed' );
		}

		return $revision;
}



// Pushes the page content to the target wikis.
protected function doPush( Title $title, array $revision, array $targets ) { debugLog ("ApiPush:doPush\n");
  foreach ( $targets as $target ) {
    $token = $this->getToken( $target, 'csrf' );
    if ( $token !== false ) {
      $doPush = true;
      Hooks::run( 'PushAPIBeforePush', [ &$title, &$revision, &$target, &$token, &$doPush ] );
      if ( $doPush ) {$this->pushToTarget( $title, $revision, $target, $token );}
    }  }  }




	//  Pushes the page content to the specified wiki.

protected function pushToTarget( Title $title, array $revision, $target, $token ) { debugLog ("ApiPush:pushToTarget\n");
  global $wgSitename;
  $summary = wfMessage('push-import-revision-message', $wgSitename
			// $revision['user']
		)->parse();

  $requestData = ['action' => 'edit', 'title' => $title->getFullText(), 'format' => 'json', 'summary' => $summary, 'text' => $revision['*'], 'token' => $token,];
  $req = MWHttpRequest::factory( $target, [ 'method' => 'POST', 'timeout' => 'default', 'postData' => $requestData ], __METHOD__ );
  if ( array_key_exists( $target, $this->cookieJars ) ) { $req->setCookieJar( $this->cookieJars[$target] ); }
  $status = $req->execute();

		if ( $status->isOK() ) {
			$response = $req->getContent();
			$this->editResponses[] = $response;
			Hooks::run( 'PushAPIAfterPush', [ $title, $revision, $target, $token, $response ] );
		} else {
			$this->dieWithError( wfMessage( 'push-special-err-push-failed' )->text(), 'page-push-failed' );
		}
	}


public function getAllowedParams() { debugLog ("ApiPush:getAllowedParameters\n");
  return ['page'    =>  [ ApiBase::PARAM_TYPE => 'string', ApiBase::PARAM_ISMULTI => true, ApiBase::PARAM_REQUIRED => true,],
          'targets' =>  [ ApiBase::PARAM_TYPE => 'string', ApiBase::PARAM_ISMULTI => true, ApiBase::PARAM_REQUIRED => true, ],
  ];
}

protected function getExamplesMessages() { return [ 'action=push&page=Main page&targets=http://en.wikipedia.org/w' => 'apihelp-push-example', ]; }

}












/**
 * API module to push images to other MediaWiki wikis.
 *
 * @since 0.5
 *
 * @file ApiPushImages.php
 * @ingroup Push
 *
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ApiPushImages extends ApiPushBase {

	public function __construct( $main, $action ) {
		parent::__construct( $main, $action );
	}

	/**
	 * @param array $targetsForProcessing We have to process defined targets only for security reasons
	 */
	public function doModuleExecute( array $targetsForProcessing ) {
		$params = $this->extractRequestParams();

		foreach ( $params['images'] as $image ) {
			$title = Title::newFromText( $image, NS_FILE );
			if ( $title !== null && $title->getNamespace() == NS_FILE && $title->exists() ) {
				$this->doPush( $title, $targetsForProcessing );
			}
		}
	}

	/**
	 * Pushes the page content to the target wikis.
	 *
	 * @since 0.5
	 *
	 * @param Title $title
	 * @param array $targets
	 */
	protected function doPush( Title $title, array $targets ) {
		foreach ( $targets as $target ) {
			$token = $this->getToken( $target, 'csrf' );

			if ( $token !== false ) {
				$doPush = true;

				Hooks::run( 'PushAPIBeforeImagePush', [ &$title, &$target, &$token, &$doPush ] );

				if ( $doPush ) {
					$this->pushToTarget( $title, $target, $token );
				}
			}
		}
	}

	/**
	 * Pushes the image to the specified wiki.
	 *
	 * @since 0.5
	 *
	 * @param Title $title
	 * @param string $target
	 * @param string $token
	 */
	protected function pushToTarget( Title $title, $target, $token ) {
		global $egPushDirectFileUploads;

		$imagePage = new ImagePage( $title );

		$requestData = [
			'action' => 'upload',
			'format' => 'json',
			'token' => $token,
			'filename' => $title->getText(),
			'ignorewarnings' => '1'
		];

		if ( $egPushDirectFileUploads ) {
			$file = $imagePage->getFile();
			$be = $file->getRepo()->getBackend();
			$localFile = $be->getLocalReference(
				[ 'src' => $file->getPath() ]
			);
			if ( function_exists( 'curl_file_create' ) ) {
				$requestData['file'] = curl_file_create( $localFile->getPath() );
			} else {
				$requestData['file'] = '@' . $localFile->getPath();
			}
		} else {
			$requestData['url'] = $imagePage->getDisplayedFile()->getFullUrl();
		}

		$reqArgs = [
			'method' => 'POST',
			'timeout' => 'default',
			'postData' => $requestData
		];

		if ( $egPushDirectFileUploads ) {
			if ( !function_exists( 'curl_init' ) ) {
				$this->dieWithError(
					wfMessage( 'push-api-err-nocurl' )->text(),
					'image-push-nocurl'
				);
			} elseif (
				!defined( 'CurlHttpRequest::SUPPORTS_FILE_POSTS' )
				|| !CurlHttpRequest::SUPPORTS_FILE_POSTS
			) {
				$this->dieWithError(
					wfMessage( 'push-api-err-nofilesupport' )->text(),
					'image-push-nofilesupport'
				);
			} else {
				$httpEngine = Http::$httpEngine;
				Http::$httpEngine = 'curl';
				$req = MWHttpRequest::factory( $target, $reqArgs, __METHOD__ );
				Http::$httpEngine = $httpEngine;
			}
		} else {
			$req = MWHttpRequest::factory( $target, $reqArgs, __METHOD__ );
		}

		if ( array_key_exists( $target, $this->cookieJars ) ) {
			$req->setCookieJar( $this->cookieJars[$target] );
		}

		$req->setHeader( 'Content-Type', 'multipart/form-data' );

		$status = $req->execute();

		if ( $status->isOK() ) {
			$response = $req->getContent();

			$this->getResult()->addValue(
				null,
				null,
				FormatJson::decode( $response )
			);

			Hooks::run( 'PushAPIAfterImagePush', [ $title, $target, $token, $response ] );
		} else {
			$this->dieWithError( wfMessage( 'push-special-err-push-failed' )->text(), 'page-push-failed' );
		}
	}

public function getAllowedParams() {
  return ['images' => [ApiBase::PARAM_TYPE => 'string', ApiBase::PARAM_ISMULTI => true, ApiBase::PARAM_REQUIRED => true,],	
      'targets' => [ ApiBase::PARAM_TYPE => 'string',	ApiBase::PARAM_ISMULTI => true, ApiBase::PARAM_REQUIRED => true, ],];
}


protected function getExamplesMessages() { return ['action=pushimages&images=File:Foo.bar&targets=http://en.wikipedia.org/w' => 'apihelp-pushimages-example',]; }

}









