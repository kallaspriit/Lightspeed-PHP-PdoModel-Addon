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
 * @id $Id: ArrayDataSource.php 101 2011-03-17 15:48:44Z kallaspriit $
 * @author $Author: kallaspriit $
 * @version $Revision: 101 $
 * @modified $Date: 2011-03-17 17:48:44 +0200 (Thu, 17 Mar 2011) $
 * @package Lightspeed
 * @subpackage DataSource
 */

// Require base class
require_once LIBRARY_PATH.'/data-source/DataSource.php';

/**
 * This can be used to paginate an array of data.
 *
 * @id $Id: ArrayDataSource.php 101 2011-03-17 15:48:44Z kallaspriit $
 * @author $Author: kallaspriit $
 * @version $Revision: 101 $
 * @modified $Date: 2011-03-17 17:48:44 +0200 (Thu, 17 Mar 2011) $
 * @package Lightspeed
 * @subpackage DataSource
 */
class ArrayDataSource implements DataSource {

	/**
	 * Array of data to paginate.
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * Sets the data to paginate.
	 *
	 * @param array $data Data to paginate
	 */
	public function __construct(array $data) {
		$this->data = $data;
	}

	/**
	 * Returns $limit items starting from $offset. Offset is zero-based.
	 *
	 * @param integer $offset Offset from where to slice data from
	 * @param integer $limit Maximum number of items to take from offset
	 * @return array
	 */
	public function getItems($offset, $limit) {
		return array_slice($this->data, $offset, $limit);
	}

	/**
	 * Returns the number of items the container contains.
	 *
	 * @return integer Number of container items
	 */
	public function count() {
		return count($this->data);
	}
}