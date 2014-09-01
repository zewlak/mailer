<?php
/**
 * MailerService.php 2014-08-31 04:11
 * ----------------------------------------------
 * 
 *
 * @author      Stanislav Kiryukhin <korsar.zn@gmail.com>
 * @copyright   Copyright (c) 2014, CKGroup.ru
 *
 * @version 	0.0.1
 * ----------------------------------------------
 * All Rights Reserved.
 * ----------------------------------------------
 */
namespace sKSoft\Phalcon\Mailer;

use Phalcon\Mvc\User\Component;
use Phalcon\Mvc\View;
use Phalcon\Config;

/**
 * Class Mailer
 * @package sKSoft\Phalcon\Mailer
 */
class Mailer extends Component
{
	/**
	 * @var array
	 */
	protected $config;

	/**
	 * @var mixed
	 */
	protected $transport;

	/**
	 * @var \Swift_Mailer
	 */
	protected $mailer;

	/**
	 * @var View\Simple
	 */
	protected $view;

	/**
	 * @param array $config
	 */
	public function __construct(Array $config)
	{
		$this->configure($config);
	}

	/**
	 * @return \Swift_Mailer
	 */
	public function getSwift()
	{
		return $this->mailer;
	}

	/**
	 * @param $view
	 * @param array $params
	 * @param null $viewsDir
	 *
	 * @return Message
	 */
	public function createMessageViaView($view, $params = [], $viewsDir = null)
	{
		$message = $this->createMessage();
		$message->content($this->renderView($view, $params, $viewsDir), 'text/html');


		return $message;
	}

	/**
	 * @return \sKSoft\Phalcon\Mailer\Message
	 */
	public function createMessage()
	{
		$eventsManager = $this->getEventsManager();

		if($eventsManager)
			$eventsManager->fire('mailer:beforeCreateMessage', $this);

		$message = $this->getDI()->get('\sKSoft\Phalcon\Mailer\Message', [$this]);

		if($eventsManager)
			$eventsManager->fire('mailer:afterCreateMessage', $this, [$message]);

		return $message;
	}


	/**
	 * Register SwiftMailer
	 */
	protected function registerSwiftMailer()
	{
		$this->mailer = new \Swift_Mailer($this->transport);
	}


	protected function registerSwiftTransport()
	{
		switch($driver = $this->getConfig('driver'))
		{
			case 'smtp':
				$this->transport = $this->registerTransportSmtp();
			break;

			case 'mail':
				$this->transport = $this->registerTransportMail();
			break;

			case 'sendmail':
				$this->transport = $this->registerTransportSendmail();
			break;

			default:
				throw new \InvalidArgumentException(sprintf('Driver-mail "%s" is not supported', $driver));
		}
	}

	/**
	 * @return \Swift_SmtpTransport
	 */
	protected function registerTransportSmtp()
	{
		$config = $this->getConfig();

		$transport = \Swift_SmtpTransport::newInstance($config['host'], $config['port']);

		if(isset($config['encryption']))
			$transport->setEncryption($config['encryption']);

		if(isset($config['username']))
		{
			$transport->setUsername($config['username']);
			$transport->setPassword($config['password']);
		}

		return $transport;
	}

	/**
	 * @return \Swift_SendmailTransport
	 */
	protected function registerTransportSendmail()
	{
		return \Swift_SendmailTransport::newInstance($this->getConfig('sendmail', '/usr/sbin/sendmail -bs'));
	}

	/**
	 * @return \Swift_MailTransport
	 */
	protected function registerTransportMail()
	{
		return \Swift_MailTransport::newInstance();
	}

	/**
	 * @param $config
	 */
	protected function configure(Array $config)
	{
		$this->config = $config;

		$this->registerSwiftTransport();
		$this->registerSwiftMailer();
	}

	/**
	 * @param null $key
	 * @param null $default
	 *
	 * @return mixed
	 */
	protected function getConfig($key = null, $default = null)
	{
		if($key !== null)
		{
			if(isset($this->config[$key]))
				return $this->config[$key];
			else
				return $default;
		}
		else
			return $this->config;
	}

	/**
	 * @param $viewPath
	 * @param $params
	 * @param null $viewsDir
	 *
	 * @return string
	 */
	protected function renderView($viewPath, $params, $viewsDir = null)
	{
		$view = $this->getView();

		if($viewsDir !== null)
		{
			$viewsDirOld = $view->getViewsDir();
			$view->setViewsDir($viewsDir);

			$content = $view->render($viewPath, $params);
			$view->setViewsDir($viewsDirOld);

			return $content;
		}
		else
			return $view->render($viewPath, $params);
	}

	/**
	 * @return View\Simple
	 */
	protected function getView()
	{
		if($this->view)
			return $this->view;
		else
		{
			/** @var $view \Phalcon\Mvc\View\Simple */
			$view = $this->getDI()->get('\Phalcon\View\Simple');
			$view->setViewsDir($this->getConfig('viewsDir'));

			return $this->view = $view;
		}
	}
} 