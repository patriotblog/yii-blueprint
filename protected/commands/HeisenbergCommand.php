<?php

/**
 * This command will check all new modules and add them to ACL access db
 * it also will check the "theaccessrule" function to assign default access on system
 *
 * @author saeed
 */
class HeisenbergCommand extends CConsoleCommand
{


    public function actionIndex()
    {



        print "Hello And Welcome To Heisenberg Command line \n";
        print "Here We Want U to Add SuperAdmin \n";
        print "..:Created By Aydin Abedinia:.. \n";
        print "First Step I want u to insert username for admin  \n";

        echo "\n\nPLz Insert Your Username :";
        $username = trim(fgets(STDIN));

        echo "\n\n";
        print "Second Step I want u to insert Password for ".$username."  \n";

        echo "\n\nPLz Insert Your password :";
        $password = trim(fgets(STDIN));
        echo "\n\n";echo "\n\n";

        echo "Your Username is : ".$username;
        echo " & Your Password is : ".$password;

        echo "\n\n I create admin now ?";
        echo "\n\n yes or no - type y or n : ";
        echo "\n\n";
        $bool = trim(fgets(STDIN));
        if($bool == 'y'){





            $check = Yii::app()->db->schema->getTable('user');
            if($check == null){
                $runner=new CConsoleCommandRunner();
                $runner->commands=array(
                    'migrate' => array(
                        'class' => 'system.cli.commands.MigrateCommand',
                        'interactive' => false,
                    ),
                );

                ob_start();
                $runner->run(array(
                    'yiic',
                    'migrate',

                ));
            }

            $criteria = new CDbCriteria();
            $criteria->addCondition("username=:username");
            $criteria->params = array(':username' => $username);
            $superuser = User::model()->findAll($criteria);

            if(count($superuser) == 0){
                $model = new UserEntity();
                $model->email = "a@a.a";
                $model->username = $username;
                $model->password = CPasswordHelper::hashPassword($password);
                $model->created_time = time();
                $model->save();
                echo "\n\n superadmin inserted to DB - U Can login with user&pass \n\n";
            }else{
                $hashedpass = CPasswordHelper::hashPassword($password);
                User::model()->updateAll(array('password'=>$hashedpass), $criteria);

                echo "\n\n superadmin exist in DB - new password Updated \n\n";

            }
        }else{
            echo "\n\n no \n\n";
        }

    }










}

?>