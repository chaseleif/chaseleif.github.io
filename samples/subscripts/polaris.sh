#!/bin/bash -l
#PBS -N perfvar
#PBS -l select=10:ncpus=640
#PBS -l place=scatter
#PBS -l filesystems=home
#PBS -l walltime=1:00:00
#PBS -q normal
#PBS -A accountname

# Polaris compute nodes
# Cores/Threads  32/64
# RAM            512 GiB
# NUMA nodes     4
# GPUs           4
# Local SSD      3.2TB

# name         nodes     time
#             min-max   min-max
# debug         1-2    5min-1hr    8 exclusive debug nodes, max 24
# debug-scaling 1-10   5min-1hr    max 1 job (running/queued) per user
# prod         10-496  5min-24hr   routed automatically
# prod routes to one of:
# small        10-24   5min-3hr
# medium       25-99   5min-6hr
# large       100-496  5min-24hr
# backfill-small                   backfill queues have the same limits
# backfill-medium                                       low priority
# backfill-large                                        negative balance

# directory where job submitted
cd $PBS_O_WORKDIR

# setup the environment
module load cray-python/3.11.5
source venv/bin/activate

# number of nodes
NNODES=`wc -l < $PBS_NODEFILE`
# number of ranks per node
NRANKS=16
# number of hardware threads per rank, i.e., spacing between ranks
NDEPTH=4
# number of software threads per rank, i.e., OMP_NUM_THREADS
NTHREADS=1
# total number of ranks
TOTALRANKS=$((NNODES*NRANKS))

date

mpiexec \
  --np ${TOTALRANKS} -ppn ${NRANKS} -d ${NDEPTH} --cpu-bind numa \
  -env OMP_NUM_THREADS=${NTHREADS} \
  python3 -m mpi4py driver.py --input=data/runtime.csv --generate

date
