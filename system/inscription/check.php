<?php
# choix des étapes
if (CTR::$get->get('step') == 1 || !CTR::$get->exist('step')) {
	if (CTR::$get->exist('bindkey')) {
		CTR::$data->add('prebindkey', CTR::$get->get('bindkey'));
		CTR::redirect('inscription');
	} elseif (CTR::$data->exist('prebindkey')) {
		# utilisation de l'API
		$api = new API(GETOUT_ROOT);

		if ($api->userExist(CTR::$data->get('prebindkey'))) {
			include_once ZEUS;
			$S_PAM_INSCR = ASM::$pam->getCurrentSession();
			ASM::$pam->newSession();
			ASM::$pam->load(array('bind' => CTR::$data->get('prebindkey')));

			if (ASM::$pam->size() == 0) {
				CTR::$data->add('inscription', new ArrayList());
				CTR::$data->get('inscription')->add('bindkey', CTR::$data->get('prebindkey'));
				CTR::$data->get('inscription')->add('portalPseudo', $api->data['userInfo']['pseudo']);
			} else {
				header('Location: ' . GETOUT_ROOT . 'accueil/speak-badinscription');
				exit();
			}
			ASM::$pam->changeSession($S_PAM_INSCR);
		} else {
			header('Location: ' . GETOUT_ROOT . 'accueil/speak-badinscription');
			exit();
		}
	} else {
		header('Location: ' . GETOUT_ROOT . 'accueil/speak-badinscription');
		exit();
	}
} elseif (CTR::$get->get('step') == 2) {
	if (CTR::$data->exist('inscription')) {
		# création du tableau des alliances actives
			# entre 1 et 7
			# alliance pas défaites
			# algorythme de fermeture automatique des alliances (auto-balancing)
		$ally = array(1, 5, 6);
		if (CTR::$get->exist('ally') && in_array(CTR::$get->get('ally'), $ally)) {
			CTR::$data->get('inscription')->add('ally', CTR::$get->get('ally'));
		} elseif (!CTR::$data->get('inscription')->exist('ally')) {
			CTR::$alert->add('faction inconnues ou non-sélectionnable');
			CTR::redirect('inscription/bindkey-' . CTR::$data->get('inscription')->get('bindkey'));
		}
	} else {
		header('Location: ' . GETOUT_ROOT . 'accueil/speak-badinscription');
		exit();
	}
} elseif (CTR::$get->get('step') == 3) {
	if (CTR::$data->exist('inscription')) {
		# check nom dejà utilisé
		include_once ZEUS;
		$S_PAM_INSCR2 = ASM::$pam->getCurrentSession();
		ASM::$pam->newSession();
		ASM::$pam->load(array('name' => CTR::$post->get('pseudo')));
		if (ASM::$pam->size() == 0) {
			include_once ZEUS;
			$check = new CheckName();

			if (CTR::$post->exist('pseudo') && $check->checkLength(CTR::$post->get('pseudo')) && $check->checkChar(CTR::$post->get('pseudo'))) {
				CTR::$data->get('inscription')->add('pseudo', CTR::$post->get('pseudo'));

				# check avatar
				if (TRUE) {
					CTR::$data->get('inscription')->add('avatar', CTR::$post->get('avatar'));
				} elseif (!CTR::$data->get('inscription')->exist('avatar')) {
					CTR::$alert->add('cet avatar n\'existe pas ou est invalide');
					CTR::redirect('inscription/step-2');
				}
			} elseif (!CTR::$data->get('inscription')->exist('pseudo')) {
				CTR::$alert->add('votre pseudo est trop long, trop court ou contient des caractères non-autorisés');
				CTR::redirect('inscription/step-2');
			}
		} elseif (!CTR::$data->get('inscription')->exist('pseudo')) {
			CTR::$alert->add('ce pseudo est déjà utilisé par un autre joueur');
			CTR::redirect('inscription/step-2');
		}
		ASM::$pam->changeSession($S_PAM_INSCR2);
	} else {
		header('Location: ' . GETOUT_ROOT . 'accueil/speak-badinscription');
		exit();
	}
} elseif (CTR::$get->get('step') == 4) {
	include_once ZEUS;
	$S_PAM_INSCR = ASM::$pam->getCurrentSession();
	ASM::$pam->newSession();
	ASM::$pam->load(array('bind' => CTR::$data->get('bindkey')));

	if (ASM::$pam->size() == 0) {
		if (CTR::$data->exist('inscription')) {
			include_once ZEUS;
			$check = new CheckName();

			if (CTR::$post->exist('base') && $check->checkLength(CTR::$post->get('base')) && $check->checkChar(CTR::$post->get('base'))) {
				CTR::$data->get('inscription')->add('base', CTR::$post->get('base'));

				if (in_array(CTR::$post->get('sector'), array(16, 17, 3, 4, 12, 15))) {
					CTR::$data->get('inscription')->add('sector', CTR::$post->get('sector'));
				} else {
					CTR::$alert->add('le secteur choisi n\'existe pas ou n\'est pas disponible pour votre faction');
					CTR::redirect('inscription/step-3');
				}
			} else {
				CTR::$alert->add('le nom de votre base est trop long, trop court ou contient des caractères non-autorisés');
				CTR::redirect('inscription/step-3');
			}
		} else {
			header('Location: ' . GETOUT_ROOT . 'accueil/speak-badinscription');
			exit();
		}
	} else {
		header('Location: ' . GETOUT_ROOT . 'accueil/speak-badinscription');
		exit();
	}

	ASM::$pam->changeSession($S_PAM_INSCR);
}
?>