<?php
namespace frontend\controllers;


use common\models\UserExt;
use frontend\models\Category;
use frontend\models\form\ChatForm;
use frontend\models\form\PostForm;
use frontend\models\form\SignForm;
use frontend\models\form\SiteForm;
use Yii;
use yii\base\InvalidParamException;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\LoginForm;
use frontend\models\PasswordResetRequestForm;
use frontend\models\ResetPasswordForm;
use frontend\models\SignupForm;
use frontend\models\ContactForm;

/**
 * Site controller
 */
class SiteController extends BaseController
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout', 'signup'],
                'rules' => [
                    [
                        'actions' => ['signup'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [ //验证码
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
				'height' => 50,
				'width' => 80,
				'minLength' => 4,
				'maxLength' => 4
            ],
        ];
    }

    public function actionUnIpa()
	{//解包获取bundle_ID
		$filename = Yii::getAlias('@frontend').'/web/source/';
		$res = scandir($filename);
		unset($res[0]);
		unset($res[1]);
		foreach ($res as $v)
		{
			$file = $filename.$v;
			$ipa_info[] = SiteForm::unzipIpa($file);
			$res = array_pop($ipa_info);
			echo "{$v}----------------------包名:".$res['bundle_id']."------------------应用名称:{$res['app_name']}".PHP_EOL.'<br/>';
		}
	}

	public function actionTest()
	{
		echo "hello world";
	}
    /**
     * Displays homepage.
     *
     * @return mixed
     *
     */
    public function actionIndex()
    {
		$uid = Yii::$app->user->id;
    	$dynamic_newest = PostForm::getTheNewestDynamic(); //最新动态
		$category_arr = Category::find()->asArray()->all();
		$data = [];
		if (!empty($category_arr)){
			foreach ($category_arr as $v)
			{
				$data[$v['name']] = PostForm::PostInfo($v['id']);
			}
		}
		$chat_newest = ChatForm::getThenewestChat(); //最新聊天内容
		$sign_data = SignForm::QuerySign();
		$banner = SiteForm::bannerInfo();
		return $this->render('index',[
        	'dynamic' => $dynamic_newest,
			'data' => $data,
			'banner' => $banner,
			'chat' => $chat_newest,
			'uid' => $uid,
			'sign_data' => $sign_data
		]);
    }

	public function actionSquare()
	{
		return $this->render('square');
	}

    /**
     * Logs in a user.
     *
     * @return mixed
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
        	$user_obj = UserExt::findOne(['user_id'=>Yii::$app->user->id]);
			$user_obj->last_log_in = date('Y-m-d H:i:s', time());
			$user_obj->save(); //更新最后登陆时间
            return $this->goBack();
        } else {
            $model->password = '';

            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Logs out the current user.
     *
     * @return mixed
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return mixed
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail(Yii::$app->params['adminEmail'])) {
                Yii::$app->session->setFlash('success', 'Thank you for contacting us. We will respond to you as soon as possible.');
            } else {
                Yii::$app->session->setFlash('error', 'There was an error sending your message.');
            }

            return $this->refresh();
        } else {
            return $this->render('contact', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Displays about page.
     *
     * @return mixed
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

    /**
     * Signs user up.
     *
     * @return mixed
     */
    public function actionSignup()
    {
        $model = new SignupForm();
        if ($model->load(Yii::$app->request->post())) {
            if ($user = $model->signup()) {
                if (Yii::$app->getUser()->login($user)) {
                    return $this->goHome();
                }
            }
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    /**
     * Requests password reset.
     *
     * @return mixed
     */
    public function actionRequestPasswordReset()
    {
        $model = new PasswordResetRequestForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', '已发送一封邮件该邮箱，请根据邮箱的指示进行下一步操作.');

                return $this->goHome();
            } else {
                Yii::$app->session->setFlash('error', '对不起，我们无法为所提供的电子邮件地址重置密码。');
            }
        }

        return $this->render('requestPasswordResetToken', [
            'model' => $model,
        ]);
    }

    /**
     * Resets password.
     *
     * @param string $token
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function actionResetPassword($token)
    {
        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidParamException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->session->setFlash('success', '修改密码成功！');

            return $this->goHome();
        }

        return $this->render('resetPassword', [
            'model' => $model,
        ]);
    }
}
