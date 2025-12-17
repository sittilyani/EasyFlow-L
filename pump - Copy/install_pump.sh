#!/bin/bash
# install_pump.sh

echo "Installing MasterPlex Pump System..."

# Create pump directory
mkdir -p ../pump

# Set permissions
sudo chmod 755 ../pump
sudo chown www-data:www-data ../pump  # Adjust for your web server user

# Install required PHP extensions
echo "Checking/installing PHP extensions..."
sudo apt-get update
sudo apt-get install -y php-cli php-common php-mysql

# Check for serial extensions
if ! php -m | grep -q "dio"; then
    echo "Installing PHP DIO extension..."
    sudo apt-get install -y php-dio
fi

# Install stty for serial communication
sudo apt-get install -y coreutils

# Set up udev rules for FT232R
echo "Setting up udev rules for FT232R..."
cat << EOF | sudo tee /etc/udev/rules.d/99-ft232r.rules
SUBSYSTEM=="tty", ATTRS{idVendor}=="0403", ATTRS{idProduct}=="6001", MODE="0666", SYMLINK+="ttyFT232"
SUBSYSTEM=="usb", ATTRS{idVendor}=="0403", ATTRS{idProduct}=="6001", MODE="0666"
EOF

# Reload udev rules
sudo udevadm control --reload-rules
sudo udevadm trigger

echo "Installation complete!"
echo "Please run: sudo chmod 666 /dev/ttyUSB0 (or appropriate port)"
echo "Test the system by visiting: ../pump/test_pump.php"