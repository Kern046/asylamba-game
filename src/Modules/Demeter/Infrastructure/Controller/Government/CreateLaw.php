<?php

namespace App\Modules\Demeter\Infrastructure\Controller\Government;

use App\Classes\Entity\EntityManager;
use App\Classes\Exception\ErrorException;
use App\Classes\Library\Parser;
use App\Classes\Library\Utils;
use App\Modules\Athena\Manager\CommercialTaxManager;
use App\Modules\Demeter\Manager\ColorManager;
use App\Modules\Demeter\Manager\Law\LawManager;
use App\Modules\Demeter\Model\Color;
use App\Modules\Demeter\Model\Law\Law;
use App\Modules\Demeter\Resource\ColorResource;
use App\Modules\Demeter\Resource\LawResources;
use App\Modules\Gaia\Manager\SectorManager;
use App\Modules\Zeus\Manager\PlayerManager;
use App\Modules\Zeus\Model\Player;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CreateLaw extends AbstractController
{
	public function __invoke(
		Request $request,
		Player $currentPlayer,
		ColorManager $colorManager,
		CommercialTaxManager $commercialTaxManager,
		LawManager $lawManager,
		PlayerManager $playerManager,
		EntityManager $entityManager,
		SectorManager $sectorManager,
		Parser $parser,
	): Response {
		$type = $request->query->get('type');
		$duration = (int) $request->request->get('duration');

		if (false !== $type) {
			if (LawResources::size() >= $type) {
				if ($currentPlayer->getStatus() == LawResources::getInfo($type, 'department')) {
					$faction = $colorManager->get($currentPlayer->getRColor());
					$law = new Law();

					$law->rColor = $currentPlayer->getRColor();
					$law->type = $type;
					if (Player::CHIEF == LawResources::getInfo($type, 'department')) {
						$law->statement = Law::EFFECTIVE;

						$law->dCreation = Utils::now();
						$law->dEndVotation = Utils::now();

						if (LawResources::getInfo($type, 'undeterminedDuration')) {
							$date = new \DateTime(Utils::now());
							$date->modify('+'. 5 .' years');
							$law->dEnd = $date->format('Y-m-d H:i:s');
						} elseif ($duration) {
							$duration = ($duration > 2400) ? 2400 : $duration;
							$date = new \DateTime(Utils::now());
							$date->modify('+'.$duration.' hours');
							$law->dEnd = $date->format('Y-m-d H:i:s');
						} else {
							$law->dEnd = Utils::now();
						}
					} else {
						$law->statement = Law::VOTATION;

						$date = new \DateTime(Utils::now());
						$law->dCreation = $date->format('Y-m-d H:i:s');
						$date->modify('+'.Law::VOTEDURATION.' second');
						$law->dEndVotation = $date->format('Y-m-d H:i:s');

						if (LawResources::getInfo($type, 'undeterminedDuration')) {
							$date = new \DateTime($law->dEndVotation);
							$date->modify('+'. 5 .' years');
							$law->dEnd = $date->format('Y-m-d H:i:s');
						} elseif ($duration) {
							if ($duration > 2400) {
								$duration = 2400;
							} elseif ($duration < 1) {
								$duration = 1;
							}
							$date = new \DateTime($law->dEndVotation);
							$date->modify('+'.$duration.' hours');
							$law->dEnd = $date->format('Y-m-d H:i:s');
						} else {
							$law->dEnd = Utils::now();
						}
					}
					if (LawResources::getInfo($type, 'bonusLaw')) {
						if (false !== $duration) {
							if ($faction->credits >= LawResources::getInfo($type, 'price') * $duration * $faction->activePlayers) {
								$law->options = serialize([]);

								if (!$lawManager->lawExists($currentPlayer->getRColor(), $type)) {
									$lawManager->add($law);
									$faction->credits -= LawResources::getInfo($type, 'price') * $duration * $faction->activePlayers;
									$colorManager->sendSenateNotif($faction);
								} else {
									throw new ErrorException('Cette loi est déjà proposée ou en vigueur.');
								}
							} else {
								throw new ErrorException('Il n\'y a pas assez de crédits dans les caisses de l\'Etat.');
							}
						}
					} else {
						if ($faction->credits >= LawResources::getInfo($type, 'price')) {
							switch ($type) {
								case Law::SECTORTAX:
									$taxes = intval($request->request->get('taxes'));
									$rSector = $request->request->get('rsector');
									if (false !== $taxes && false !== $rSector) {
										if ($taxes >= 2 && $taxes <= 15) {
											if (($sector = $sectorManager->get($rSector)) !== null) {
												if ($sector->rColor == $currentPlayer->getRColor()) {
													$law->options = serialize(['taxes' => $taxes, 'rSector' => $rSector, 'display' => ['Secteur' => $sector->name, 'Taxe actuelle' => $sector->tax.' %', 'Taxe proposée' => $taxes.' %']]);
													$lawManager->add($law);
													$faction->credits -= LawResources::getInfo($type, 'price');
													$colorManager->sendSenateNotif($faction);
												} else {
													throw new ErrorException('Ce secteur n\'est pas sous votre contrôle.');
												}
											} else {
												throw new ErrorException('Ce secteur n\'existe pas.');
											}
										} else {
											throw new ErrorException('La taxe doit être entre 2 et 15 %.');
										}
									} else {
										throw new ErrorException('Informations manquantes.');
									}
									break;
								case Law::SECTORNAME:
									$rSector = $request->request->get('rsector');
									$name = $request->request->get('name');
									if (false !== $rSector && false !== $name) {
										if (strlen($name) >= 1 and strlen($name) <= 50) {
											$name = $parser->protect($name);
											if (($sector = $sectorManager->get($rSector)) !== null) {
												if ($sector->rColor == $currentPlayer->getRColor()) {
													$law->options = serialize(['name' => $name, 'rSector' => $rSector]);
													$lawManager->add($law);
													$faction->credits -= LawResources::getInfo($type, 'price');
													$colorManager->sendSenateNotif($faction, true);
												} else {
													throw new ErrorException('Ce secteur n\'est pas sous votre contrôle.');
												}
											} else {
												throw new ErrorException('Ce secteur n\'existe pas.');
											}
										} else {
											throw new ErrorException('Le nom doit faire entre 1 et 50 caractères.');
										}
									} else {
										throw new ErrorException('Informations manquantes.');
									}
									break;
								case Law::COMTAXEXPORT:
									$taxes = intval($request->request->get('taxes'));
									$rColor = $request->request->get('rcolor');
									if (false !== $taxes && false !== $rColor) {
										$_CTM = $commercialTaxManager->getCurrentsession();
										$commercialTaxManager->load(['faction' => $currentPlayer->getRColor(), 'relatedFaction' => $rColor]);
										if ($commercialTaxManager->size() > 0) {
											if ($commercialTaxManager->get()->relatedFaction == $currentPlayer->getRColor()) {
												if ($taxes <= 15) {
													$law->options = serialize(['taxes' => $taxes, 'rColor' => $rColor, 'display' => ['Faction' => ColorResource::getInfo($rColor, 'officialName'), 'Taxe actuelle' => $commercialTaxManager->get()->exportTax.' %', 'Taxe proposée' => $taxes.' %']]);
													$lawManager->add($law);
													$faction->credits -= LawResources::getInfo($type, 'price');
													$colorManager->sendSenateNotif($faction);
												} else {
													throw new ErrorException('Pas plus que 15.');
												}
											} else {
												if ($taxes <= 15 && $taxes >= 2) {
													$law->options = serialize(['taxes' => $taxes, 'rColor' => $rColor, 'display' => ['Faction' => ColorResource::getInfo($rColor, 'officialName'), 'Taxe actuelle' => $commercialTaxManager->get()->exportTax.' %', 'Taxe proposée' => $taxes.' %']]);
													$lawManager->add($law);
													$faction->credits -= LawResources::getInfo($type, 'price');
													$colorManager->sendSenateNotif($faction);
												} else {
													throw new ErrorException('Entre 2 et 15.');
												}
											}
										} else {
											throw new ErrorException('Cette faction n\'existe pas.');
										}
									} else {
										throw new ErrorException('Informations manquantes.');
									}
									break;
								case Law::COMTAXIMPORT:
									$taxes = intval($request->request->get('taxes'));
									$rColor = $request->request->get('rcolor');
									if (false !== $taxes && false !== $rColor) {
										$_CTM = $commercialTaxManager->getCurrentsession();
										$commercialTaxManager->load(['faction' => $currentPlayer->getRColor(), 'relatedFaction' => $rColor]);
										if ($commercialTaxManager->size() > 0) {
											if ($commercialTaxManager->get()->relatedFaction == $currentPlayer->getRColor()) {
												if ($taxes <= 15) {
													$law->options = serialize(['taxes' => $taxes, 'rColor' => $rColor, 'display' => ['Faction' => ColorResource::getInfo($rColor, 'officialName'), 'Taxe actuelle' => $commercialTaxManager->get()->importTax.' %', 'Taxe proposée' => $taxes.' %']]);
													$lawManager->add($law);
													$faction->credits -= LawResources::getInfo($type, 'price');
													$colorManager->sendSenateNotif($faction);
												} else {
													throw new ErrorException('Pas plus que 15.');
												}
											} else {
												if ($taxes <= 15 && $taxes >= 2) {
													$law->options = serialize(['taxes' => $taxes, 'rColor' => $rColor, 'display' => ['Faction' => ColorResource::getInfo($rColor, 'officialName'), 'Taxe actuelle' => $commercialTaxManager->get()->importTax.' %', 'Taxe proposée' => $taxes.' %']]);
													$lawManager->add($law);
													$faction->credits -= LawResources::getInfo($type, 'price');
													$colorManager->sendSenateNotif($faction);
												} else {
													throw new ErrorException('Entre 2 et 15.');
												}
											}
										} else {
											throw new ErrorException('Cette faction n\'existe pas.');
										}
									} else {
										throw new ErrorException('Informations manquantes.');
									}
									break;
								case Law::NEUTRALPACT:
									$rColor = $request->request->get('rcolor');
									if (false !== $rColor) {
										if ($rColor >= 1 && $rColor <= (ColorResource::size() - 1) && $rColor != $faction->id) {
											if (Color::NEUTRAL != $faction->colorLink[$rColor]) {
												$law->options = serialize(['rColor' => $rColor, 'display' => ['Faction' => ColorResource::getInfo($rColor, 'officialName')]]);
												$lawManager->add($law);
												$faction->credits -= LawResources::getInfo($type, 'price');
												$colorManager->sendSenateNotif($faction, true);
											} else {
												throw new ErrorException('Vous considérez déjà cette faction comme votre alliée.');
											}
										} else {
											throw new ErrorException('Cette faction n\'existe pas ou il s\'agit de la votre.');
										}
									} else {
										throw new ErrorException('Informations manquantes.');
									}
									break;
								case Law::PEACEPACT:
									$rColor = $request->request->get('rcolor');
									if (false !== $rColor) {
										if ($rColor >= 1 && $rColor <= (ColorResource::size() - 1) && $rColor != $faction->id) {
											$nbrPact = 0;
											foreach ($faction->colorLink as $relation) {
												if (Color::PEACE == $relation) {
													++$nbrPact;
												}
											}
											if ($nbrPact < 2) {
												if (Color::PEACE != $faction->colorLink[$rColor]) {
													$law->options = serialize(['rColor' => $rColor, 'display' => ['Faction' => ColorResource::getInfo($rColor, 'officialName')]]);
													$lawManager->add($law);
													$faction->credits -= LawResources::getInfo($type, 'price');
													$colorManager->sendSenateNotif($faction, true);
												} else {
													throw new ErrorException('Vous considérez déjà cette faction comme votre alliée.');
												}
											} else {
												throw new ErrorException('Vous ne pouvez faire que 2 pactes de ce type.');
											}
										} else {
											throw new ErrorException('Cette faction n\'existe pas ou il s\'agit de la votre.');
										}
									} else {
										throw new ErrorException('Informations manquantes.');
									}
									break;
								case Law::TOTALALLIANCE:
									$rColor = $request->request->get('rcolor');
									if (false !== $rColor) {
										if ($rColor >= 1 && $rColor <= (ColorResource::size() - 1) && $rColor != $faction->id) {
											$allyYet = false;
											foreach ($faction->colorLink as $relation) {
												if (Color::ALLY == $relation) {
													$allyYet = true;
												}
											}
											if (!$allyYet) {
												if (Color::ALLY != $faction->colorLink[$rColor]) {
													$law->options = serialize(['rColor' => $rColor, 'display' => ['Faction' => ColorResource::getInfo($rColor, 'officialName')]]);
													$lawManager->add($law);
													$faction->credits -= LawResources::getInfo($type, 'price');
													$colorManager->sendSenateNotif($faction, true);
												} else {
													throw new ErrorException('Vous considérez déjà cette faction comme votre alliée.');
												}
											} else {
												throw new ErrorException('Vous ne pouvez considérez qu\'une seule faction comme alliée.');
											}
										} else {
											throw new ErrorException('Cette faction n\'existe pas ou il s\'agit de la votre.');
										}
									} else {
										throw new ErrorException('Informations manquantes.');
									}
									break;
								case Law::WARDECLARATION:
									$rColor = $request->request->get('rcolor');
									if (false !== $rColor) {
										if ($rColor >= 1 && $rColor <= (ColorResource::size() - 1) && $rColor != $faction->id) {
											if (Color::ENEMY != $faction->colorLink[$rColor]) {
												$law->options = serialize(['rColor' => $rColor, 'display' => ['Faction' => ColorResource::getInfo($rColor, 'officialName')]]);
												$lawManager->add($law);
												$faction->credits -= LawResources::getInfo($type, 'price');
												$colorManager->sendSenateNotif($faction, true);
											} else {
												throw new ErrorException('Vous considérez déjà cette faction comme votre ennemmi.');
											}
										} else {
											throw new ErrorException('Cette faction n\'existe pas ou il s\'agit de la votre.');
										}
									} else {
										throw new ErrorException('Informations manquantes.');
									}
									break;
								case Law::PUNITION:
									$rPlayer = $request->request->get('rplayer');
									$credits = $request->request->getInt('credits');

									if (null !== $rPlayer && 0 !== $credits) {
										if ($credits > 0) {
											$targetPlayer = $playerManager->get($rPlayer);
											if ($targetPlayer->rColor == $currentPlayer->getRColor()) {
												$law->options = serialize(['rPlayer' => $rPlayer, 'credits' => $credits, 'display' => ['Joueur' => $targetPlayer->name, 'amende' => $credits]]);
												$lawManager->add($law);
												$faction->credits -= LawResources::getInfo($type, 'price');
												$colorManager->sendSenateNotif($faction);
											} else {
												throw new ErrorException('Ce joueur n\'est pas de votre faction.');
											}
										} else {
											throw new ErrorException('l\'amende doit être un entier positif.');
										}
									} else {
										throw new ErrorException('Informations manquantes.');
									}
									break;
								default:
									throw new ErrorException('Cette loi n\'existe pas.');
							}
						} else {
							throw new ErrorException('Il n\'y assez pas a de crédits dans les caisses de l\'Etat.');
						}
					}
				} else {
					throw new ErrorException('Vous n\' avez pas le droit de proposer cette loi.');
				}
			} else {
				throw new ErrorException('Cette loi n\'existe pas.');
			}
		} else {
			throw new ErrorException('Informations manquantes.');
		}
		$entityManager->flush();

		return $this->redirectToRoute('faction_senate');
	}
}
