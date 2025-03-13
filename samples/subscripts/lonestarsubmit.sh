#!/bin/bash
#SBATCH -A myaccount      # account name
#SBATCH -J myjob          # job name
#SBATCH -o /dev/null      # name of the output and error file
#SBATCH -N 1              # total number of nodes requested
#SBATCH -n 68             # total number of tasks requested
#SBATCH -p flat-quadrant  # queue name normal or development
#SBATCH -t 48:00:00       # expected maximum runtime (hh:mm:ss)

export IBRUN_QUIET=1
export IBRUN_TASKS_PER_NODE=68

output=node1
numproc=68

# For each model
for t in AP GH ANI ; do
  # Here we only do NOPACK
  for p in NOPACK ; do
    # For each size
    for x in S M L XL ; do
      # If the directory doesn't exist
      if [[ ! -d ../${t}${x}${p} ]] ; then
        continue
      fi
      # Copy the state file, this is the same for all TD iterations
      cp ../${t}${x}${p}/board.bin .
      # For each TD
      for td in TD2 TD4 TD12 ; do
        # If we already have our output file
        if [[ -f ../${t}${x}${td}/${output} ]] ; then
          continue
        fi
        # If we don't have the program
        if [[ ! -f ../${t}${x}${td}/MPI_OMP ]] ; then
          continue
        fi
        cp ../${t}${x}${td}/MPI_OMP .
        # We have done zero iterations
        iterations=0
        # If the output file exists, we must be resuming
        if [[ -f ${output} ]] ; then
          # Each iteration makes 8 lines in the output file
          iterations=$(($(wc -l ${output} | cut -d' ' -f1)/8))
        fi
        # Complete 5 iterations
        for ((i = 5 ; i > ${iterations} ; --i)) ; do
          ibrun -n ${numproc} numactl --preferred=1 ./MPI_OMP ${output}
        done
        # Move our output file to the test directory
        mv ${output} ../${t}${x}${td}/
        # Remove this program
        rm -f MPI_OMP
        sleep 1
      done
      # Remove other files
      rm -f board.bin node*
    done
  done
done

