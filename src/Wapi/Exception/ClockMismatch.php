<?php

namespace Wapi\Exception;

class ClockMismatch extends WapiException  {
  const CODE = 9;
  const MESSAGE = 'Clock mismatch.';
}