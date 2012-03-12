<?php namespace Kuna;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Nassau\Acl\Acl;
use Nassau\Acl\Rule;

class Controller {
	private $authManager = null;
	private $db = null;
	private $acl = null;
  public function __construct(\PDO $db, AuthManager $authManager, Acl $acl) {
  	// to wszystko są serwisy!  TODO
  	$this->authManager = $authManager;
  	$this->acl = $acl;
  	$this->db = $db;
  }

	public function handle(Request $request) {
		$this->request = $request;

		$limits = array ();
//		$rest = $request -> getBaseUrl();
		list ($rest) = explode('?', $request->getRequestUri());
		
		$rest = ltrim($rest, '/');

		$params = $this->getParams($request);
		$params->set('rest', $rest);
		$params->set('method', $request->getMethod());


		$id = null;
		if (preg_match('#(.*)/+(\d+)$#', $rest, $m)) {
			list (, $rest, $id) = $m;
		}
		$rest = preg_replace_callback('#([a-z])/(\d+)#', function ($x) use ($limits) {
			$limit = $x[1];
			$limits[$limit] = $x[2];
			return $limit;
		}, $rest);
		
  	$context = $this->authManager->validate($params);
    if (false === $context) switch ($this->authManager->getError()):
    case AuthManager::ERR_MISSING_PARAM:
    	return $this->prepareResponse('Missing some of the required params: developer_id, sig, nonce', 400);
    case AuthManager::ERR_EXPIRED_NONCE:
    	return $this->prepareResponse('Nonce has expired', 408);
    case AuthManager::ERR_DEVELOPER_SIG:
    	return $this->prepareResponse('Invalid developer_id', 401);
    case AuthManager::ERR_BAD_SIGNATURE:
    	return $this->prepareResponse('Invalid signature', 401);
    default:
    	
          // TODO
      return;
    endswitch;

		$user = Acl::createTarget($context->getUser(), $context->getGroups());
    $access = $this->acl->getAccessLevel($user, 'api/noun/' . $rest);
    
    if (Rule::PERM_NONE == $access) {
    	return $this->prepareResponse('Forbidden', 403);
    }
    
    $reqAccess = Rule::PERM_READ;
    switch ($request->getMethod()):
    case 'POST':
    case 'PUT';
    case 'PATCH':
    	$reqAccess |= Rank::PERM_WRITE;
    	break;
    case 'DELETE':
    	$reqAccess |= Rank::PERM_DELETE;
    endswitch;
    
    if ($reqAccess != ($access & $reqAccess)) {
    	return $this->prepareRequest('Forbidden', 403);
    }
    
   	$isAdmin = $this->acl->hasAccess($user, 'api/manage');
    
		$api = new Api($this->db);
		$manager = $api->getManager($rest);
		
		if (!$isAdmin) {
			$app = $api->applications->getByDeveloperId(
				$request->get('developer_id'),
				$request->get('subdomain')
			);
			if (sizeof($app) > 1) {
				return $this->prepareResponse('Multiple apps registered. Provide unique domain', 300);
			} elseif (!$app) {
				return $this->prepareResponse('No application registered to this developer_id/domain', 404);
			}
			list ($app) = $app;
			$game = $api->games->getCurrentGame($app->id);

			if (!$game) {
				return $this->prepareResponse('No game currently running for this app', 404);
			}
			
			if (empty($rest)) {
				// init:
				return $this->prepareResponse(Array ('app' => $app, 'game' => $game));
			}
			if ($manager) $manager->limit($game);
		}
		
		if (!$manager) {
			return $this->prepareResponse('Invalid resource requested: "' . $rest . '"', 404);
		}
		
		$manager->setPrimaryKey($id);
		foreach ($limits as $limitName => $id) {
			$limit = $api->$limitName->getById($id);
			if ($limit) {
				$manager -> limit($limit);
			} else {
				return $this->prepareResponse('Required dependency missing: "' . $limitName . '"', 404);
			}
		}
		
		try { switch ($request->getMethod()):
		case 'POST':
			// create
			$object = $manager->create($params);
			return $this->prepareResponse($object, 201);
		case 'PUT':
			// update
			$object = $manager->update($params);
			return $this->prepareResponse($object, 200);
			
		case 'GET':
		case 'HEAD':
			$object = $manager->getByParams($params->all());
			return $this->prepareResponse($object, 200);
			
		case 'DELETE':
			$manager->delete();
			return $this->prepareRespnse($object, 204);
		default:
			return $this->prepareResponse('Request method not supported by that resource', 405);
		
		endswitch; } catch (Exception $E) {
			// not found ?
			// required parameter missing
		
		};

	}
	private function getParams (Request $request) {
		$params = new \Symfony\Component\HttpFoundation\ParameterBag(
			array_merge($request->query->all(), $request->request->all())
		);
		return $params;
	}
	private function prepareResponse($content, $code = 200) {
		$code = (int)$code;
		$content = is_array($content) ? $content : array('message' => $content);
		list ($ct) = $this->request->getAcceptableContentTypes();
		if ($this->request) switch ($this->request->getFormat($ct)) {
//		case 'txt':
//			$content = print_r($content, true);
//			break;
		default:
			$content = json_encode($content);
			$ct = 'application/json';
			// links?
			// created?
			// ... TODO
		}
		$response = new Response($content, $code, Array('Content-type' => $ct));
		return $response;
	}
}
