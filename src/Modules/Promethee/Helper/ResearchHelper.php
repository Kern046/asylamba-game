<?php

namespace App\Modules\Promethee\Helper;

use App\Classes\Exception\ErrorException;
use App\Modules\Promethee\Resource\ResearchResource;

class ResearchHelper
{
    public function __construct(
        protected int $researchCoeff,
        protected int $researchMaxDiff
    ) {
    }

    public function isAResearch($research)
    {
        return in_array($research, ResearchResource::$availableResearch);
    }

    public function getInfo($research, $info, $level = 0, $sup = 'delfault')
    {
        if ($this->isAResearch($research)) {
            if ('name' == $info || 'codeName' == $info) {
                return ResearchResource::$research[$research][$info];
            } elseif ('level' == $info) {
                if ($level <= 0) {
                    return false;
                }
                if ('price' == $sup) {
                    return $this->researchPrice($research, $level) * $this->researchCoeff;
                }
            } else {
                throw new ErrorException('Wrong second argument for method getInfo() from ResearchResource');
            }
        } else {
            throw new ErrorException('This research doesn\'t exist !');
        }

        return false;
    }

    public function isResearchPermit(int $firstLevel, int $secondLevel, int $thirdLevel = -1): bool
    {
        // compare the levels of technos and say if you can research such techno
        if (-1 == $thirdLevel) {
            if (abs($firstLevel - $secondLevel) > $this->researchMaxDiff) {
                return false;
            } else {
                return true;
            }
        } else {
            if (abs($firstLevel - $secondLevel) > $this->researchMaxDiff) {
                return false;
            } elseif (abs($firstLevel - $thirdLevel) > $this->researchMaxDiff) {
                return false;
            } elseif (abs($secondLevel - $thirdLevel) > $this->researchMaxDiff) {
                return false;
            } else {
                return true;
            }
        }
    }

    private function researchPrice($research, $level)
    {
        switch ($research) {
            case 0 :
                if (1 == $level) {
                    return 100;
                } else {
                    return round((0.0901 * pow($level, 5)) - (12.988 * pow($level, 4)) + (579.8 * pow($level, 3)) - (5735.8 * pow($level, 2)) + (28259 * $level) - 25426);
                    // ancienne : return round((-4451.2 * pow($level, 3)) + (138360 * pow($level, 2)) - (526711 * $level) + 589669);
                }
                break;
            case 1 :
                if (1 == $level) {
                    return 3000;
                } else {
                    return round((0.0901 * pow($level, 5)) - (12.988 * pow($level, 4)) + (579.8 * pow($level, 3)) - (5735.8 * pow($level, 2)) + (28259 * $level) - 25426);
                }
                break;
            case 2 :
                if (1 == $level) {
                    return 7000;
                } else {
                    return round((0.0901 * pow($level, 5)) - (12.988 * pow($level, 4)) + (579.8 * pow($level, 3)) - (5735.8 * pow($level, 2)) + (28259 * $level) - 25426);
                }
                break;
            case 3 :
                if (1 == $level) {
                    return 200;
                } else {
                    return round((0.0901 * pow($level, 5)) - (12.988 * pow($level, 4)) + (579.8 * pow($level, 3)) - (5735.8 * pow($level, 2)) + (28259 * $level) - 25426);
                }
                break;
            case 4 :
                if (1 == $level) {
                    return 9000;
                } else {
                    return round((0.0901 * pow($level, 5)) - (12.988 * pow($level, 4)) + (579.8 * pow($level, 3)) - (5735.8 * pow($level, 2)) + (28259 * $level) - 25426);
                }
                break;
            case 5 :
                if (1 == $level) {
                    return 200;
                } else {
                    return round((0.0901 * pow($level, 5)) - (12.988 * pow($level, 4)) + (579.8 * pow($level, 3)) - (5735.8 * pow($level, 2)) + (28259 * $level) - 25426);
                }
                break;
            case 6 :
                if (1 == $level) {
                    return 9000;
                } else {
                    return round((0.0901 * pow($level, 5)) - (12.988 * pow($level, 4)) + (579.8 * pow($level, 3)) - (5735.8 * pow($level, 2)) + (28259 * $level) - 25426);
                }
                break;
            case 7 :
                if (1 == $level) {
                    return 200;
                } else {
                    return round((0.0901 * pow($level, 5)) - (12.988 * pow($level, 4)) + (579.8 * pow($level, 3)) - (5735.8 * pow($level, 2)) + (28259 * $level) - 25426);
                }
                break;
            case 8 :
                if (1 == $level) {
                    return 4000;
                } else {
                    return round((0.0901 * pow($level, 5)) - (12.988 * pow($level, 4)) + (579.8 * pow($level, 3)) - (5735.8 * pow($level, 2)) + (28259 * $level) - 25426);
                }
                break;
            case 9 :
                if (1 == $level) {
                    return 6000;
                } else {
                    return round((0.0901 * pow($level, 5)) - (12.988 * pow($level, 4)) + (579.8 * pow($level, 3)) - (5735.8 * pow($level, 2)) + (28259 * $level) - 25426);
                }
                break;
            default:
                return false;
        }
    }
}
