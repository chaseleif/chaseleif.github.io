#! /usr/bin/env python3

from math import sqrt
from sys import argv, set_int_max_str_digits
# larger values of n require this to be increased
#set_int_max_str_digits((1<<31)-1)
# The 10,000,000th fibonacci number has 2089877 digits
# This will handle at least values of n between +/-10000000
set_int_max_str_digits(2089877)

def getfib(n):
  negative = n < 0
  if negative:
    n = -n
    negative = (n&1) == 0
  if n == 0: fib = lambda n: 0
  elif n == 1: fib = lambda n: 1
  elif n < 71:
    fib = lambda n: round( ((1+sqrt(5))**n - (1-sqrt(5))**n) \
                                / (2**n * sqrt(5)) )
  else:
    fib = lambda n: pow(2<<n, n+1, (4<<2*n)-(2<<n)-1) % (2<<n)
  return -fib(n) if negative else fib(n)

if __name__=='__main__':
  n = int(argv[1]) if len(argv) == 2 else 42
  print(getfib(n))
