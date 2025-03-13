#!/bin/bash
#SBATCH -J dpdrv             # Job name
#SBATCH -o dpdrv.o%j         # Name of stdout output file
#SBATCH -e dpdrv.e%j         # Name of stderr error file
#SBATCH -p nvdimm            # Queue (partition) name
#SBATCH -N 1                 # Total # of nodes 
#SBATCH --exclusive          # No other jobs on the nodes
#SBATCH --tasks-per-node=80  # Tasks per node
#SBATCH --cpus-per-task=1    # Number of CPUs available per task
#SBATCH -t 05:00:00          # Run time (hh:mm:ss)
#SBATCH -A accountname       # Project/allocation

module load python/3.9.18
module load mvapich/3.0 intel/24.0

./install.sh

source venv/bin/activate

ntasks="$((SLURM_JOB_NUM_NODES*SLURM_NTASKS_PER_NODE))"

echo "Starting $ntasks tasks"
date
mpiexec -n $ntasks python3 -m mpi4py \
  ./driver.py -i=data/runtime.csv --generate >> "ltime$ntasks"
date

mv data/*.npy gen/

ntasks=72

while [ "$ntasks" -gt 0 ] ; do
  echo "Starting $ntasks tasks"
  date
  mpiexec -n $ntasks python3 -m mpi4py \
    ./driver.py -i=data/runtime.csv --generate >> "ltime$ntasks"
  date
  if [ "$ntasks" -eq 72 ] ; then
    ntasks=64
  else
    ntasks="$((ntasks/2))"
  fi
done
