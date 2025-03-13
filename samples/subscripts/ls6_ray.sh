#!/bin/bash
#SBATCH -J myjob            # Job name
#SBATCH -o myjob.o%j        # Name of stdout output file
#SBATCH -e myjob.e%j        # Name of stderr error file
#SBATCH -p development      # Queue (partition) name
#SBATCH -N 3                # Total # of nodes 
#SBATCH --exclusive         # Nodes must be exclusive for Ray
#SBATCH --tasks-per-node=1  # Ensure each Ray worker runs on a different node
#SBATCH --cpus-per-task=128 # Number of CPUs available per task
#SBATCH -t 00:01:00         # Run time (hh:mm:ss)
#SBATCH -A accountname      # Project/allocation

date

# Ray is installed in venv
source venv/bin/activate

# Gather node list in the allocation
nodes=$(scontrol show hostnames "$SLURM_JOB_NODELIST")
nodes_array=($nodes)
# The first node will act as the head
head_node=${nodes_array[0]}
# The ip address and port
head_node_ip=$(srun --nodes=1 --ntasks=1 -w "$head_node" hostname --ip-address)
port=6379
ip_head=$head_node_ip:$port
# A fairly random password
redis_password=$(uuidgen)

echo "head_node = \"$head_node\""
echo "ip_head = \"$ip_head\""

# Start 1 task for the head
# We would use srun with SLURM, but TACC provides ibrun
#srun --nodes=1 --ntasks=1 -w "$head_node" \

ibrun -n 1 -o 0 \
    ray start --head --node-ip-address="$head_node_ip" --port=$port \
    --redis-password="$redis_password" --disable-usage-stats \
    --num-cpus 1 --block &

# The number of GPUs could also be specified
#--num-gpus "${SLURM_GPUS_PER_TASK}"

# Allow the head node to complete startup before others attempt to connect
sleep 10

# The number of other nodes in the allocation
worker_num=$((SLURM_JOB_NUM_NODES - 1))

#srun -n $worker_num --nodes=$worker_num --ntasks-per-node=1 \
#    --exclude $head_node \
ibrun -n $worker_num -o 1 \
    ray start --address "$ip_head" \
    --redis-password="$redis_password" --disable-usage-stats \
    --num-cpus 1 --block &
#--num-gpus "${SLURM_GPUS_PER_TASK}"

# Try to ensure everyone is started+connected before we run our task
# This sleep may not be necessary
sleep 5

# Run the script (from the head node) as if it were serial
python raydemo.py

hostname

date
