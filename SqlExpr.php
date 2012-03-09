<?php
/**
 * Lightspeed high-performance hiphop-php optimized PHP framework
 *
 * Copyright (C) <2011> by <Priit Kallas>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @id $Id: SqlExpr.php 65 2011-03-01 16:01:06Z kallaspriit $
 * @author $Author: kallaspriit $
 * @version $Revision: 65 $
 * @modified $Date: 2011-03-01 18:01:06 +0200 (Tue, 01 Mar 2011) $
 * @package Lightspeed
 * @subpackage Model
 */

/**
 * Represents an SQL expression that should be included in the query as-is not
 * as a string.
 *
 * @id $Id: SqlExpr.php 65 2011-03-01 16:01:06Z kallaspriit $
 * @author $Author: kallaspriit $
 * @version $Revision: 65 $
 * @modified $Date: 2011-03-01 18:01:06 +0200 (Tue, 01 Mar 2011) $
 * @package Lightspeed
 * @subpackage Model
 */
class SqlExpr {

	/**
	 * The expression
	 *
	 * @var string
	 */
	private $expression;

	/**
	 * Sets the expression to use.
	 *
	 * @param string $expression Expression to use
	 */
	public function __construct($expression) {
		$this->expression = $expression;
	}

	/**
	 * Returns the expression when cast to a string
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->expression;
	}
}