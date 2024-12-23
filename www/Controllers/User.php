<?php
namespace App\Controllers;

use App\Model\User as UserModel;
use App\Core\View;
use App\Core\Messages;

class User
{
    /**
     * The method to register a user and redirect to the home page after successful registration
     * @return void
     */
    public function register(): void
    {
        $userModel = new UserModel();
         if ($userModel->isLogged()) {
             Messages::setMessage('Vous êtes déjà connecté', 'error');
             header("Location: /");
             exit;
         }
        $view = new View("User/register.php", "front.php");
		$view->addData('title', 'Page d\'inscription');
        $view->addData('description', 'Inscrivez-vous pour accéder à toutes les fonctionnalités de notre site');
        $data = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Early return if necessary data is missing
            if (empty($_POST['email']) || empty($_POST['password'])) {
				Messages::setMessage('Veuillez remplir tous les champs', 'error');
                return;
            }
            // Sanitize $_POST super global
            $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
			// Check if the password and confirm password match
			if ($_POST['password'] !== $_POST['confirm_password']) {
				Messages::setMessage('Les mots de passe ne correspondent pas', 'error');
				return;
			}

            $formData = [
                'email' => strtolower(trim($_POST['email'])),
                'password' => trim($_POST['password']),
				'firstname' => trim($_POST['firstname']),
				'lastname' => trim($_POST['lastname'])
            ];

            $result = $userModel->insertUser($formData);
            if ($result['messageType'] === 'danger' || $result['messageType'] === 'error') {
                // Set message in a session on Error.
                Messages::setMessage($result['message'], $result['messageType']);
            } else {
                // On success, redirect to the home page with a success message.
                Messages::setMessage($result['message'], 'success');
                // Unset password from the user object before storing it in the session.
                unset($result['user']->password);
                $_SESSION['user'] = $result['user'];
                header("Location: /");
                exit;
            }
        }
    }

    public function login(): void
    {
        $view = new View("User/login.php", "front.php"); // Appeler la bonne vue
        $view->addData('title', '');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { // Vérifier que la methode est POST
            Messages::setMessage('Method not allowed', 'danger');
            return;
        }

        if (empty($_POST['email']) && empty($_POST['password'])) { // Vérifier que les champs sont remplies
            Messages::setMessage('Veuillez remplir les champs !', 'danger');
            return;
        }
        $userModel = new UserModel();
        $result = $userModel->checkPassword($_POST['email'], $_POST['password']); // Vérifier que le password est correct
        if ($result['messageType'] === 'danger') {
            Messages::setMessage($result['message'], 'error');
            return;
        }
        // Si toutes les vérifications sont OK
        $_SESSION['user'] = $result['user'];
        header("Location: /");
        exit;
    }
    public function logout(): void
    {
        $user = new UserModel();
        $user->logout();
        header("Location: /");
    }
}
