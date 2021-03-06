<?php
/**
 * Mod11
 * @package UKBankModulus
 * @license http://opensource.org/licenses/mit-license.php The MIT License
 */

#   -----------------------------------------------------------------------    #
#    Permission is hereby granted, free of charge, to any person               #
#    obtaining a copy of this software and associated documentation            #
#    files (the "Software"), to deal in the Software without                   #
#    restriction, including without limitation the rights to use,              #
#    copy, modify, merge, publish, distribute, sublicense, and/or sell         #
#    copies of the Software, and to permit persons to whom the                 #
#    Software is furnished to do so, subject to the following                  #
#    conditions:                                                               #
#                                                                              #
#    The above copyright notice and this permission notice shall be            #
#    included in all copies or substantial portions of the Software.           #
#                                                                              #
#    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,           #
#    EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES           #
#    OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND                  #
#    NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT               #
#    HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,              #
#    WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING              #
#    FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR             #
#    OTHER DEALINGS IN THE SOFTWARE.                                           #
# ============================================================================ # 

namespace UKBankModulus\Method
{
    class Mod11 implements IMethod
    {
        private $_weights = array();
        private $_exception = 0;
        private $_sortCode;
        private $_replace = false;

        public $stop = false;

        /**
         * Constructor
         * @param string $sortCode
         */
        public function __construct($sortCode) {
            $this->_sortCode = $sortCode;
        }

        public function setSortCodeReplacement($sortCode) {
            $this->_replace = $sortCode;
        }

        /**
         * Assign the weight for each digit position as define in specification
         * @param string $key Position
         * @param int $weight Weight
         */
        public function assignWeight($key, $weight) {
            $this->_weights[$key] = $weight;
        }
        /**
         * Define any exception rule to be used
         * @param int $ruleId The rule ID to use
         */
        public function withExceptionRule($ruleId) {
            $this->_exception = $ruleId;
        }
        /**
         * Return the exception number
         * @return int
         */
        public function getExceptionRule() {
            return $this->_exception;
        }
        /**
         * Validate the given account number using the Mod11 method
         * @param int $accountNumber
         * @return bool
         */
        public function validate($accountNumber) {
            $sortCode = str_split($this->_sortCode);
            if($this->_replace) {
                $sortCode = str_split($this->_replace);
            }
            $account = str_split($accountNumber);

            if($this->_exception == 7) {
                if($account[6] == "9") {
                    $this->_weights['u'] = 0;
                    $this->_weights['v'] = 0;
                    $this->_weights['w'] = 0;
                    $this->_weights['x'] = 0;
                    $this->_weights['y'] = 0;
                    $this->_weights['z'] = 0;
                    $this->_weights['a'] = 0;
                    $this->_weights['b'] = 0;
                }
            }

            if($this->_exception == 6) {
                $weights = array(4,5,6,7,8);
                if(in_array((int)$account[0], $weights) && ($account[6] == $account[7])) {
                    $this->stop = true;
                    return true; //Check cannot be used, assumed valid
                }
            }

            if($this->_exception == 10) {
                if(($account[0] == "0" || $account[0] == "9") && $account[1] == "9" && $account[6] == "9") {
                    $this->_weights['u'] = 0;
                    $this->_weights['v'] = 0;
                    $this->_weights['w'] = 0;
                    $this->_weights['x'] = 0;
                    $this->_weights['y'] = 0;
                    $this->_weights['z'] = 0;
                    $this->_weights['a'] = 0;
                    $this->_weights['b'] = 0;                   
                }
            }

            $weightedTotal = 0;
            $weightedTotal += (int)$sortCode[0] * $this->_weights['u'];
            $weightedTotal += (int)$sortCode[1] * $this->_weights['v'];
            $weightedTotal += (int)$sortCode[2] * $this->_weights['w'];
            $weightedTotal += (int)$sortCode[3] * $this->_weights['x'];
            $weightedTotal += (int)$sortCode[4] * $this->_weights['y'];
            $weightedTotal += (int)$sortCode[5] * $this->_weights['z'];
            $weightedTotal += (int)$account[0] * $this->_weights['a'];
            $weightedTotal += (int)$account[1] * $this->_weights['b'];
            $weightedTotal += (int)$account[2] * $this->_weights['c'];
            $weightedTotal += (int)$account[3] * $this->_weights['d'];
            $weightedTotal += (int)$account[4] * $this->_weights['e'];
            $weightedTotal += (int)$account[5] * $this->_weights['f'];
            $weightedTotal += (int)$account[6] * $this->_weights['g'];
            $weightedTotal += (int)$account[7] * $this->_weights['h'];

            $modulus = $weightedTotal % 11;

            $checkOne = (($modulus === 0) ? true : false);

            if($this->_exception == 4) {
                $checkDigit = $account[6] . $account[7];
                if($modulus == (int)$checkDigit) {
                    return true;
                } else {
                    return false;
                }
            }

            //Exception 5
            if($this->_exception == 5) {
                $remainder = 11 - $modulus;
                if(($modulus === 0 && $account[6] === "0") || ($remainder === (int)$account[6] && $modulus != 1)) {
                    return true;
                } else {
                    return false;
                }
            }

            //Exception 14
            if($this->_exception == 14) {
                $validRange = array(0,1,9);
                if(!in_array((int)$account[7], $validRange)) {
                    return false;
                }
                unset($account[7]);
                $accountNumber = "0" . implode("", $account);
                $account = str_split($accountNumber);

                $weightedTotal = 0;
                $weightedTotal += $sortCode[0] * $this->_weights['u'];
                $weightedTotal += $sortCode[1] * $this->_weights['v'];
                $weightedTotal += $sortCode[2] * $this->_weights['w'];
                $weightedTotal += $sortCode[3] * $this->_weights['x'];
                $weightedTotal += $sortCode[4] * $this->_weights['y'];
                $weightedTotal += $sortCode[5] * $this->_weights['z'];
                $weightedTotal += $account[0] * $this->_weights['a'];
                $weightedTotal += $account[1] * $this->_weights['b'];
                $weightedTotal += $account[2] * $this->_weights['c'];
                $weightedTotal += $account[3] * $this->_weights['d'];
                $weightedTotal += $account[4] * $this->_weights['e'];
                $weightedTotal += $account[5] * $this->_weights['f'];
                $weightedTotal += $account[6] * $this->_weights['g'];
                $weightedTotal += $account[7] * $this->_weights['h'];

                $modulus = $weightedTotal % 11;

                return (($modulus === 0) ? true : false);
            }

            return $checkOne;
        }
    }
}
