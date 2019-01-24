Vagrant.configure(2) do |config|

  config.vm.box = "bento/ubuntu-18.04"

  # Disable the new default behavior introduced in Vagrant 1.7, to
  # ensure that all Vagrant machines will use the same SSH key pair.
  # See https://github.com/mitchellh/vagrant/issues/5005
  config.ssh.insert_key = false

  # give others access to ansible roles
  config.vm.provision 'preemptively give others write access to /etc/ansible/roles', type: :shell, inline: <<~'EOM'
    sudo mkdir /etc/ansible/roles -p
    sudo chmod o+w /etc/ansible/roles
  EOM

  # Run Ansible from the Vagrant VM
  config.vm.provision 'ansible', run: 'always', type: :ansible_local do |ansible|
    ansible.galaxy_role_file = 'ansible/requirements.yml'
    ansible.galaxy_roles_path = '/etc/ansible/roles'
    ansible.galaxy_command = 'ansible-galaxy install --role-file=%{role_file} --roles-path=%{roles_path}'
    ansible.playbook = 'ansible/vagrant.yml'
  end

  config.vm.network "forwarded_port", guest: 80, host: 8080
  config.vm.network "private_network", ip: "10.0.0.10"

  config.vm.synced_folder ".", "/vagrant", type: "nfs"
end