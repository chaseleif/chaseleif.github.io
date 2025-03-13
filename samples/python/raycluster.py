#! /usr/bin/env python3

import ray

# Initialize the Ray framework
ray.init()

# Return the local hostname
@ray.remote(num_cpus=1, scheduling_strategy="SPREAD")
class LocalHostname:
  def get_hostname(self):
    with open('/etc/hostname','r') as infile:
      hostname = infile.read().strip()
    return hostname

if __name__ == "__main__":
  # Instantiate each agent as a Ray actor
  hosts = [LocalHostname.remote() for _ in range(3)]
  print([ray.get(host.get_hostname.remote()) for host in hosts])

