#!/bin/bash

#═══════════════════════════════════════════════════════════════════════════════
#                         WIZARD PANEL INSTALLER v0.1.4
#                    Automated Installation Script for Linux
#═══════════════════════════════════════════════════════════════════════════════

# ═══════════════════════════════════════════════════════════════════════════════
# COLOR DEFINITIONS
# ═══════════════════════════════════════════════════════════════════════════════
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
WHITE='\033[1;37m'
BOLD='\033[1m'
NC='\033[0m'

# ═══════════════════════════════════════════════════════════════════════════════
# GLOBAL VARIABLES
# ═══════════════════════════════════════════════════════════════════════════════
PROJECT_URL="https://github.com/poryajp/wizardpanel/archive/refs/tags/0.1.4.zip"
WP_DIR="/root/ols/wp"
DB_DIR="/root/ols/db"
OLS_DIR="/root/ols"
COMPOSE_CMD=""
PKG_MANAGER=""
OS=""

# ═══════════════════════════════════════════════════════════════════════════════
# PRINT FUNCTIONS
# ═══════════════════════════════════════════════════════════════════════════════
print_banner() {
    clear
    echo -e "${CYAN}"
    echo "╔═══════════════════════════════════════════════════════════════════════╗"
    echo "║                                                                       ║"
    echo "║     ██╗    ██╗██╗███████╗ █████╗ ██████╗ ██████╗                      ║"
    echo "║     ██║    ██║██║╚══███╔╝██╔══██╗██╔══██╗██╔══██╗                     ║"
    echo "║     ██║ █╗ ██║██║  ███╔╝ ███████║██████╔╝██║  ██║                     ║"
    echo "║     ██║███╗██║██║ ███╔╝  ██╔══██║██╔══██╗██║  ██║                     ║"
    echo "║     ╚███╔███╔╝██║███████╗██║  ██║██║  ██║██████╔╝                     ║"
    echo "║      ╚══╝╚══╝ ╚═╝╚══════╝╚═╝  ╚═╝╚═╝  ╚═╝╚═════╝                      ║"
    echo "║                                                                       ║"
    echo "║                   🧙 PANEL INSTALLER v0.1.4 🧙                        ║"
    echo "║                                                                       ║"
    echo "╚═══════════════════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
    echo ""
}

print_step() {
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BLUE}[▶]${NC} ${WHITE}${BOLD}$1${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

print_info() {
    echo -e "${CYAN}   ℹ${NC}  ${WHITE}$1${NC}"
}

print_success() {
    echo -e "${GREEN}   ✓${NC}  ${WHITE}$1${NC}"
}

print_error() {
    echo -e "${RED}   ✗${NC}  ${WHITE}$1${NC}"
}

print_warning() {
    echo -e "${YELLOW}   ⚠${NC}  ${WHITE}$1${NC}"
}

print_separator() {
    echo -e "${PURPLE}───────────────────────────────────────────────────────────────────────${NC}"
}

# ═══════════════════════════════════════════════════════════════════════════════
# ERROR HANDLING
# ═══════════════════════════════════════════════════════════════════════════════
error_exit() {
    print_error "$1"
    exit 1
}

# ═══════════════════════════════════════════════════════════════════════════════
# CHECK ROOT
# ═══════════════════════════════════════════════════════════════════════════════
check_root() {
    print_step "Checking Root Privileges"
    if [ "$(id -u)" != "0" ]; then
        print_error "This script must be run as root!"
        print_info "Please run: sudo bash $0"
        exit 1
    fi
    print_success "Running as root user"
}

# ═══════════════════════════════════════════════════════════════════════════════
# OS DETECTION (از اسکریپت مرجع)
# ═══════════════════════════════════════════════════════════════════════════════
detect_os() {
    print_step "Detecting Operating System"
    
    if [ -f /etc/lsb-release ]; then
        OS=$(lsb_release -si)
    elif [ -f /etc/os-release ]; then
        OS=$(awk -F= '/^NAME/{print $2}' /etc/os-release | tr -d '"')
    elif [ -f /etc/redhat-release ]; then
        OS=$(cat /etc/redhat-release | awk '{print $1}')
    elif [ -f /etc/arch-release ]; then
        OS="Arch"
    else
        print_warning "Could not detect OS, will try generic methods"
        OS="Unknown"
    fi
    
    print_success "Detected: $OS"
}

# ═══════════════════════════════════════════════════════════════════════════════
# PACKAGE MANAGER DETECTION AND UPDATE (از اسکریپت مرجع)
# ═══════════════════════════════════════════════════════════════════════════════
detect_and_update_package_manager() {
    print_info "Detecting and updating package manager..."
    
    if [[ "$OS" == *"Ubuntu"* ]] || [[ "$OS" == *"Debian"* ]]; then
        PKG_MANAGER="apt-get"
        $PKG_MANAGER update -qq >/dev/null 2>&1 || true
    elif [[ "$OS" == *"CentOS"* ]] || [[ "$OS" == *"AlmaLinux"* ]] || [[ "$OS" == *"Rocky"* ]]; then
        PKG_MANAGER="yum"
        $PKG_MANAGER update -y -q >/dev/null 2>&1 || true
        $PKG_MANAGER install -y -q epel-release >/dev/null 2>&1 || true
    elif [[ "$OS" == *"Fedora"* ]]; then
        PKG_MANAGER="dnf"
        $PKG_MANAGER update -q -y >/dev/null 2>&1 || true
    elif [[ "$OS" == *"Arch"* ]]; then
        PKG_MANAGER="pacman"
        $PKG_MANAGER -Sy --noconfirm --quiet >/dev/null 2>&1 || true
    elif [[ "$OS" == *"openSUSE"* ]]; then
        PKG_MANAGER="zypper"
        $PKG_MANAGER refresh --quiet >/dev/null 2>&1 || true
    else
        # Try to detect package manager
        if command -v apt-get >/dev/null 2>&1; then
            PKG_MANAGER="apt-get"
            $PKG_MANAGER update -qq >/dev/null 2>&1 || true
        elif command -v yum >/dev/null 2>&1; then
            PKG_MANAGER="yum"
        elif command -v dnf >/dev/null 2>&1; then
            PKG_MANAGER="dnf"
        elif command -v pacman >/dev/null 2>&1; then
            PKG_MANAGER="pacman"
        else
            print_warning "Could not detect package manager"
        fi
    fi
    
    print_success "Package manager: $PKG_MANAGER"
}

# ═══════════════════════════════════════════════════════════════════════════════
# INSTALL PACKAGE (از اسکریپت مرجع)
# ═══════════════════════════════════════════════════════════════════════════════
install_package() {
    local PACKAGE=$1
    print_info "Installing $PACKAGE..."
    
    if [ -z "$PKG_MANAGER" ]; then
        detect_and_update_package_manager
    fi
    
    case $PKG_MANAGER in
        apt-get)
            $PKG_MANAGER -y -qq install "$PACKAGE" >/dev/null 2>&1 || $PKG_MANAGER -y install "$PACKAGE"
            ;;
        yum)
            $PKG_MANAGER install -y -q "$PACKAGE" >/dev/null 2>&1 || $PKG_MANAGER install -y "$PACKAGE"
            ;;
        dnf)
            $PKG_MANAGER install -y -q "$PACKAGE" >/dev/null 2>&1 || $PKG_MANAGER install -y "$PACKAGE"
            ;;
        pacman)
            $PKG_MANAGER -S --noconfirm --quiet "$PACKAGE" >/dev/null 2>&1 || $PKG_MANAGER -S --noconfirm "$PACKAGE"
            ;;
        zypper)
            $PKG_MANAGER --quiet install -y "$PACKAGE" >/dev/null 2>&1 || $PKG_MANAGER install -y "$PACKAGE"
            ;;
        *)
            print_warning "Unknown package manager, trying common methods..."
            apt-get install -y "$PACKAGE" 2>/dev/null || yum install -y "$PACKAGE" 2>/dev/null || dnf install -y "$PACKAGE" 2>/dev/null
            ;;
    esac
}

# ═══════════════════════════════════════════════════════════════════════════════
# INSTALL DEPENDENCIES
# ═══════════════════════════════════════════════════════════════════════════════
install_dependencies() {
    print_step "Installing Required Dependencies"
    
    detect_and_update_package_manager
    
    # Install curl if not exists
    if ! command -v curl >/dev/null 2>&1; then
        install_package curl
    fi
    print_success "curl is installed"
    
    # Install wget if not exists
    if ! command -v wget >/dev/null 2>&1; then
        install_package wget
    fi
    print_success "wget is installed"
    
    # Install unzip if not exists
    if ! command -v unzip >/dev/null 2>&1; then
        install_package unzip
    fi
    print_success "unzip is installed"
    
    print_success "All dependencies installed successfully"
}

# ═══════════════════════════════════════════════════════════════════════════════
# INSTALL DOCKER (از اسکریپت مرجع - روش رسمی)
# ═══════════════════════════════════════════════════════════════════════════════
install_docker() {
    print_step "Installing Docker Engine"
    
    # Check if Docker is already installed and working
    if command -v docker >/dev/null 2>&1; then
        if docker info >/dev/null 2>&1; then
            DOCKER_VERSION=$(docker --version | awk '{print $3}' | tr -d ',')
            print_success "Docker is already installed and running (version: ${DOCKER_VERSION})"
            return 0
        else
            print_warning "Docker is installed but not running. Attempting to start..."
            systemctl start docker 2>/dev/null || service docker start 2>/dev/null || true
            sleep 3
            if docker info >/dev/null 2>&1; then
                print_success "Docker started successfully"
                return 0
            fi
            print_warning "Could not start Docker. Will reinstall..."
        fi
    fi
    
    print_info "Installing Docker using official script..."
    
    # Install Docker using official script (این روش همیشه کار میکند)
    curl -fsSL https://get.docker.com | sh
    
    # Start Docker service
    print_info "Starting Docker service..."
    if command -v systemctl >/dev/null 2>&1; then
        systemctl start docker 2>/dev/null || true
        systemctl enable docker 2>/dev/null || true
    elif command -v service >/dev/null 2>&1; then
        service docker start 2>/dev/null || true
    fi
    
    # Wait for Docker to be ready
    local attempts=0
    local max_attempts=30
    print_info "Waiting for Docker to be ready..."
    
    while [ $attempts -lt $max_attempts ]; do
        if docker info >/dev/null 2>&1; then
            break
        fi
        sleep 1
        attempts=$((attempts + 1))
    done
    
    # Verify installation
    if docker info >/dev/null 2>&1; then
        DOCKER_VERSION=$(docker --version | awk '{print $3}' | tr -d ',')
        print_success "Docker installed successfully (version: ${DOCKER_VERSION})"
    else
        error_exit "Docker installation failed! Please install Docker manually and re-run this script."
    fi
}

# ═══════════════════════════════════════════════════════════════════════════════
# DETECT DOCKER COMPOSE (از اسکریپت مرجع)
# ═══════════════════════════════════════════════════════════════════════════════
detect_compose() {
    print_step "Detecting Docker Compose"
    
    # Check if docker compose command exists
    if docker compose version >/dev/null 2>&1; then
        COMPOSE_CMD='docker compose'
        local version=$(docker compose version --short 2>/dev/null || docker compose version 2>/dev/null | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1)
        print_success "Docker Compose found: $COMPOSE_CMD (version: $version)"
    elif command -v docker-compose >/dev/null 2>&1; then
        COMPOSE_CMD='docker-compose'
        local version=$(docker-compose --version 2>/dev/null | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1)
        print_success "Docker Compose found: $COMPOSE_CMD (version: $version)"
    else
        print_warning "Docker Compose not found. Installing..."
        install_docker_compose
    fi
}

# ═══════════════════════════════════════════════════════════════════════════════
# INSTALL DOCKER COMPOSE
# ═══════════════════════════════════════════════════════════════════════════════
install_docker_compose() {
    print_info "Installing Docker Compose..."
    
    # Try installing plugin first
    if [ -n "$PKG_MANAGER" ]; then
        case $PKG_MANAGER in
            apt-get)
                $PKG_MANAGER install -y docker-compose-plugin >/dev/null 2>&1 || true
                ;;
            yum|dnf)
                $PKG_MANAGER install -y docker-compose-plugin >/dev/null 2>&1 || true
                ;;
        esac
    fi
    
    # Check if plugin installed
    if docker compose version >/dev/null 2>&1; then
        COMPOSE_CMD='docker compose'
        print_success "Docker Compose plugin installed"
        return 0
    fi
    
    # Install standalone docker-compose
    print_info "Installing Docker Compose standalone..."
    
    local COMPOSE_VERSION="v2.24.0"
    local ARCH=$(uname -m)
    
    case $ARCH in
        x86_64) ARCH="x86_64" ;;
        aarch64|arm64) ARCH="aarch64" ;;
        armv7l) ARCH="armv7" ;;
        *) ARCH="x86_64" ;;
    esac
    
    curl -SL "https://github.com/docker/compose/releases/download/${COMPOSE_VERSION}/docker-compose-linux-${ARCH}" \
        -o /usr/local/bin/docker-compose 2>/dev/null
    
    chmod +x /usr/local/bin/docker-compose
    
    if command -v docker-compose >/dev/null 2>&1; then
        COMPOSE_CMD='docker-compose'
        print_success "Docker Compose standalone installed"
    else
        error_exit "Failed to install Docker Compose!"
    fi
}

# ═══════════════════════════════════════════════════════════════════════════════
# DIRECTORY CREATION
# ═══════════════════════════════════════════════════════════════════════════════
create_directories() {
    print_step "Creating Project Directories"
    
    # Check if directory exists and not empty
    if [ -d "$WP_DIR" ] && [ "$(ls -A $WP_DIR 2>/dev/null)" ]; then
        print_warning "Directory $WP_DIR already exists and is not empty"
        echo -ne "${YELLOW}   ⚠${NC}  Do you want to remove it and start fresh? (y/N): "
        read -r REPLY
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            rm -rf "$WP_DIR"
            print_info "Removed existing directory"
        else
            print_info "Keeping existing directory"
        fi
    fi
    
    mkdir -p "$WP_DIR"
    print_success "Created: $WP_DIR"
    
    mkdir -p "$DB_DIR"
    print_success "Created: $DB_DIR"
}

# ═══════════════════════════════════════════════════════════════════════════════
# PROJECT DOWNLOAD
# ═══════════════════════════════════════════════════════════════════════════════
download_project() {
    print_step "Downloading Wizard Panel"
    
    cd "$WP_DIR" || error_exit "Cannot change to directory $WP_DIR"
    
    print_info "Downloading from GitHub..."
    
    # Remove old zip if exists
    rm -f wizardpanel.zip 2>/dev/null || true
    
    # Download
    if wget --no-verbose --show-progress "$PROJECT_URL" -O wizardpanel.zip 2>&1; then
        print_success "Download completed"
    else
        # Try with curl as fallback
        print_warning "wget failed, trying curl..."
        if curl -L -o wizardpanel.zip "$PROJECT_URL"; then
            print_success "Download completed with curl"
        else
            error_exit "Failed to download project!"
        fi
    fi
    
    print_info "Extracting files..."
    
    if ! unzip -q -o wizardpanel.zip; then
        error_exit "Failed to extract archive!"
    fi
    
    print_success "Extraction completed"
    
    # Find the extracted directory
    print_info "Moving source files..."
    
    EXTRACTED_DIR=$(find . -maxdepth 1 -type d -name "wizardpanel*" | head -1)
    
    if [ -z "$EXTRACTED_DIR" ]; then
        error_exit "Could not find extracted directory!"
    fi
    
    print_info "Found directory: $EXTRACTED_DIR"
    
    # Check if src directory exists
    if [ ! -d "$EXTRACTED_DIR/src" ]; then
        error_exit "Source directory not found in archive!"
    fi
    
    # Move contents from src to wp directory
    if [ -d "$EXTRACTED_DIR/src" ]; then
        # Copy all files including hidden ones
        cp -rf "$EXTRACTED_DIR"/src/* . 2>/dev/null || true
        cp -rf "$EXTRACTED_DIR"/src/.[!.]* . 2>/dev/null || true
    fi
    
    # Clean up
    rm -rf "$EXTRACTED_DIR"
    rm -f wizardpanel.zip
    
    # Verify
    local file_count=$(find . -maxdepth 1 -type f 2>/dev/null | wc -l)
    
    if [ "$file_count" -eq 0 ]; then
        error_exit "No files found after extraction!"
    fi
    
    print_success "Source files moved successfully ($file_count files)"
}

# ═══════════════════════════════════════════════════════════════════════════════
# USER INPUT
# ═══════════════════════════════════════════════════════════════════════════════
get_user_inputs() {
    print_step "Configuration Settings"
    
    echo ""
    echo -e "${PURPLE}╔═══════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${PURPLE}║                    📝 ENTER YOUR CONFIGURATION                        ║${NC}"
    echo -e "${PURPLE}╚═══════════════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    
    # MySQL Root Password
    while true; do
        echo -e "${CYAN}[1/6]${NC} ${WHITE}Enter MySQL Root Password:${NC}"
        read -s MYSQL_ROOT_PASSWORD
        echo
        if [ -n "$MYSQL_ROOT_PASSWORD" ]; then
            print_success "MySQL root password set"
            break
        else
            print_error "Password cannot be empty!"
        fi
    done
    
    print_separator
    
    # MySQL User
    while true; do
        echo -e "${CYAN}[2/6]${NC} ${WHITE}Enter MySQL Username:${NC}"
        read MYSQL_USER
        if [ -n "$MYSQL_USER" ]; then
            print_success "MySQL username: $MYSQL_USER"
            break
        else
            print_error "Username cannot be empty!"
        fi
    done
    
    print_separator
    
    # MySQL Password
    while true; do
        echo -e "${CYAN}[3/6]${NC} ${WHITE}Enter MySQL User Password:${NC}"
        read -s MYSQL_PASSWORD
        echo
        if [ -n "$MYSQL_PASSWORD" ]; then
            print_success "MySQL user password set"
            break
        else
            print_error "Password cannot be empty!"
        fi
    done
    
    print_separator
    
    # WordPress Port
    echo -e "${CYAN}[4/6]${NC} ${WHITE}Enter WordPress/Panel Port (default: 80):${NC}"
    read WP_PORT
    WP_PORT=${WP_PORT:-80}
    
    # Validate port number
    if ! [[ "$WP_PORT" =~ ^[0-9]+$ ]] || [ "$WP_PORT" -lt 1 ] || [ "$WP_PORT" -gt 65535 ]; then
        print_warning "Invalid port number. Using default: 80"
        WP_PORT=80
    fi
    print_success "WordPress port: $WP_PORT"
    
    print_separator
    
    # OLS Admin User
    while true; do
        echo -e "${CYAN}[5/6]${NC} ${WHITE}Enter OpenLiteSpeed Admin Username:${NC}"
        read LSWS_ADMIN_USER
        if [ -n "$LSWS_ADMIN_USER" ]; then
            print_success "OLS admin username: $LSWS_ADMIN_USER"
            break
        else
            print_error "Username cannot be empty!"
        fi
    done
    
    print_separator
    
    # OLS Admin Password
    while true; do
        echo -e "${CYAN}[6/6]${NC} ${WHITE}Enter OpenLiteSpeed Admin Password:${NC}"
        read -s LSWS_ADMIN_PASS
        echo
        if [ -n "$LSWS_ADMIN_PASS" ]; then
            print_success "OLS admin password set"
            break
        else
            print_error "Password cannot be empty!"
        fi
    done
    
    echo ""
    print_success "All configuration settings saved!"
}

# ═══════════════════════════════════════════════════════════════════════════════
# DOCKER COMPOSE FILE CREATION
# ═══════════════════════════════════════════════════════════════════════════════
create_docker_compose() {
    print_step "Creating Docker Compose Configuration"
    
    cat > "$OLS_DIR/docker-compose.yml" << EOF
version: '3.8'

services:
  db:
    image: mysql:8.0
    container_name: ols-db
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: "${MYSQL_ROOT_PASSWORD}"
      MYSQL_DATABASE: db
      MYSQL_USER: "${MYSQL_USER}"
      MYSQL_PASSWORD: "${MYSQL_PASSWORD}"
    volumes:
      - ${DB_DIR}:/var/lib/mysql
    networks:
      - ols-network
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 10
      start_period: 30s

  wordpress:
    image: litespeedtech/openlitespeed:latest
    container_name: ols-wp
    restart: unless-stopped
    depends_on:
      db:
        condition: service_healthy
    ports:
      - "${WP_PORT}:80"
      - "7080:7080"
    environment:
      TZ: Asia/Tehran
      LSWS_ADMIN_USER: "${LSWS_ADMIN_USER}"
      LSWS_ADMIN_PASS: "${LSWS_ADMIN_PASS}"
    volumes:
      - ${WP_DIR}:/var/www/vhosts/localhost/html
    networks:
      - ols-network

  phpmyadmin:
    image: phpmyadmin/phpmyadmin
    container_name: ols-phpmyadmin
    restart: unless-stopped
    depends_on:
      - db
    ports:
      - "8081:80"
    environment:
      PMA_HOST: db
      MYSQL_ROOT_PASSWORD: "${MYSQL_ROOT_PASSWORD}"
    networks:
      - ols-network

networks:
  ols-network:
    driver: bridge
EOF
    
    print_success "docker-compose.yml created at $OLS_DIR/"
}

# ═══════════════════════════════════════════════════════════════════════════════
# START CONTAINERS
# ═══════════════════════════════════════════════════════════════════════════════
start_containers() {
    print_step "Starting Docker Containers"
    
    cd "$OLS_DIR" || error_exit "Cannot change to directory $OLS_DIR"
    
    print_info "Pulling Docker images (this may take a few minutes)..."
    
    # Pull images
    $COMPOSE_CMD -f "$OLS_DIR/docker-compose.yml" pull || print_warning "Some images might not have been pulled"
    
    print_success "Images pulled"
    
    print_info "Starting containers..."
    
    # Start containers
    $COMPOSE_CMD -f "$OLS_DIR/docker-compose.yml" up -d || error_exit "Failed to start containers"
    
    # Wait for containers to start
    print_info "Waiting for containers to initialize..."
    
    local max_attempts=60
    local attempt=1
    
    while [ $attempt -le $max_attempts ]; do
        printf "\r${CYAN}   ⏳${NC}  Waiting for containers... (%d/%d)" "$attempt" "$max_attempts"
        
        # Check if all containers are running
        local running=$(docker ps --filter "name=ols-" --filter "status=running" --format "{{.Names}}" 2>/dev/null | wc -l)
        
        if [ "$running" -ge 3 ]; then
            echo ""
            print_success "All containers are running!"
            break
        fi
        
        sleep 2
        attempt=$((attempt + 1))
    done
    
    if [ $attempt -gt $max_attempts ]; then
        echo ""
        print_warning "Some containers might still be starting..."
    fi
    
    # Show container status
    echo ""
    print_info "Container Status:"
    docker ps --filter "name=ols-" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" 2>/dev/null || docker ps --filter "name=ols-"
}

# ═══════════════════════════════════════════════════════════════════════════════
# SET PERMISSIONS
# ═══════════════════════════════════════════════════════════════════════════════
set_permissions() {
    print_step "Setting Directory Permissions"
    
    # Create required directories
    RECEIPTS_DIR="/root/ols/wp/web/user/uploads/receipts"
    
    # Wait for container to create structure
    sleep 5
    
    # Create directory if it doesn't exist
    mkdir -p "$RECEIPTS_DIR"
    print_success "Created: $RECEIPTS_DIR"
    
    # Set permissions
    chmod 0777 "$RECEIPTS_DIR"
    chmod 666 ols/wp/includes/config.php
    print_success "Set permissions 0777 on receipts directory"
    
    # Set proper permissions on wp directory
    chmod -R 755 "$WP_DIR" 2>/dev/null || true
    print_info "Set permissions 755 on WP directory"
}

# ═══════════════════════════════════════════════════════════════════════════════
# GET SERVER IP
# ═══════════════════════════════════════════════════════════════════════════════
get_server_ip() {
    SERVER_IP=""
    
    # Try IPv4 first
    SERVER_IP=$(curl -s -4 --connect-timeout 3 --max-time 5 ifconfig.io 2>/dev/null | tr -d '[:space:]')
    
    # Try IPv6 if IPv4 failed
    if [ -z "$SERVER_IP" ]; then
        SERVER_IP=$(curl -s -6 --connect-timeout 3 --max-time 5 ifconfig.io 2>/dev/null | tr -d '[:space:]')
    fi
    
    # Try other services
    if [ -z "$SERVER_IP" ]; then
        for service in "icanhazip.com" "ipinfo.io/ip" "api.ipify.org" "checkip.amazonaws.com"; do
            SERVER_IP=$(curl -s --connect-timeout 3 --max-time 5 "$service" 2>/dev/null | tr -d '[:space:]')
            if [ -n "$SERVER_IP" ]; then
                break
            fi
        done
    fi
    
    # Fallback to local IP
    if [ -z "$SERVER_IP" ]; then
        SERVER_IP=$(hostname -I 2>/dev/null | awk '{print $1}')
    fi
    
    if [ -z "$SERVER_IP" ]; then
        SERVER_IP="YOUR_SERVER_IP"
    fi
}

# ═══════════════════════════════════════════════════════════════════════════════
# DISPLAY FINAL INFORMATION
# ═══════════════════════════════════════════════════════════════════════════════
display_info() {
    get_server_ip
    
    echo ""
    echo ""
    echo -e "${GREEN}╔═══════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║                                                                       ║${NC}"
    echo -e "${GREEN}║       🎉🎉🎉  INSTALLATION COMPLETED SUCCESSFULLY!  🎉🎉🎉           ║${NC}"
    echo -e "${GREEN}║                                                                       ║${NC}"
    echo -e "${GREEN}╚═══════════════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo ""
    
    # Configuration Summary
    echo -e "${CYAN}╔═══════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║                    📋 YOUR CONFIGURATION SUMMARY                      ║${NC}"
    echo -e "${CYAN}╚═══════════════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "  ${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "  ${YELLOW}                    DATABASE CONFIGURATION${NC}"
    echo -e "  ${YELLOW}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "  ${WHITE}MySQL Host:${NC}            ${GREEN}db${NC}"
    echo -e "  ${WHITE}MySQL Database:${NC}        ${GREEN}db${NC}"
    echo -e "  ${WHITE}MySQL Port:${NC}            ${GREEN}3306${NC}"
    echo -e "  ${WHITE}MySQL Root Password:${NC}   ${GREEN}${MYSQL_ROOT_PASSWORD}${NC}"
    echo -e "  ${WHITE}MySQL Username:${NC}        ${GREEN}${MYSQL_USER}${NC}"
    echo -e "  ${WHITE}MySQL User Password:${NC}   ${GREEN}${MYSQL_PASSWORD}${NC}"
    echo ""
    echo -e "  ${PURPLE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "  ${PURPLE}                 OPENLITESPEED CONFIGURATION${NC}"
    echo -e "  ${PURPLE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "  ${WHITE}Admin Username:${NC}        ${GREEN}${LSWS_ADMIN_USER}${NC}"
    echo -e "  ${WHITE}Admin Password:${NC}        ${GREEN}${LSWS_ADMIN_PASS}${NC}"
    echo -e "  ${WHITE}Admin Panel Port:${NC}      ${GREEN}7080${NC}"
    echo ""
    echo -e "  ${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "  ${BLUE}                     PORT CONFIGURATION${NC}"
    echo -e "  ${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "  ${WHITE}Wizard Panel Port:${NC}     ${GREEN}${WP_PORT}${NC}"
    echo -e "  ${WHITE}OLS Admin Port:${NC}        ${GREEN}7080${NC}"
    echo -e "  ${WHITE}phpMyAdmin Port:${NC}       ${GREEN}8081${NC}"
    echo ""
    echo ""
    
    # Access Links
    echo -e "${GREEN}╔═══════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║                         🔗 ACCESS LINKS                               ║${NC}"
    echo -e "${GREEN}╚═══════════════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "  ${BOLD}${YELLOW}★ Wizard Panel Installation Page:${NC}"
    echo -e "    ${WHITE}http://${SERVER_IP}:${WP_PORT}/install.php${NC}"
    echo ""
    echo -e "  ${BOLD}${PURPLE}★ OpenLiteSpeed Admin Panel:${NC}"
    echo -e "    ${WHITE}https://${SERVER_IP}:7080${NC}"
    echo ""
    echo -e "  ${BOLD}${BLUE}★ phpMyAdmin:${NC}"
    echo -e "    ${WHITE}http://${SERVER_IP}:8081${NC}"
    echo ""
    echo ""
    
    # Important Note
    echo -e "${YELLOW}╔═══════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${YELLOW}║                        ⚠️  IMPORTANT NOTES                            ║${NC}"
    echo -e "${YELLOW}╚═══════════════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "  ${WHITE}1.${NC} Instead of using the server IP address, you can use your"
    echo -e "     domain that has been configured with ${BOLD}Cloudflare Zero Trust${NC}"
    echo -e "     service on port ${WHITE}${WP_PORT}${NC}"
    echo ""
    echo -e "     ${CYAN}Example: https://yourdomain.com/install.php${NC}"
    echo ""
    echo -e "  ${WHITE}2.${NC} During installation, use ${BOLD}${GREEN}'db'${NC} as the MySQL host"
    echo -e "     ${RED}(NOT localhost or 127.0.0.1)${NC}"
    echo ""
    echo -e "  ${WHITE}3.${NC} Make sure ports ${WHITE}${WP_PORT}${NC}, ${WHITE}7080${NC}, and ${WHITE}8081${NC} are open in your firewall"
    echo ""
    echo ""
    
    # Quick Copy Section
    echo -e "${PURPLE}╔═══════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${PURPLE}║                     📋 QUICK COPY - DATABASE INFO                     ║${NC}"
    echo -e "${PURPLE}╚═══════════════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "  Host:     ${GREEN}db${NC}"
    echo -e "  Database: ${GREEN}db${NC}"
    echo -e "  Username: ${GREEN}${MYSQL_USER}${NC}"
    echo -e "  Password: ${GREEN}${MYSQL_PASSWORD}${NC}"
    echo ""
    echo ""
    
    echo -e "${GREEN}╔═══════════════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║        🧙 Thank you for using Wizard Panel! Happy hosting! 🧙        ║${NC}"
    echo -e "${GREEN}║              GitHub: https://github.com/poryajp/wizardpanel           ║${NC}"
    echo -e "${GREEN}╚═══════════════════════════════════════════════════════════════════════╝${NC}"
    echo ""
}

# ═══════════════════════════════════════════════════════════════════════════════
# MAIN FUNCTION
# ═══════════════════════════════════════════════════════════════════════════════
main() {
    print_banner
    
    check_root
    detect_os
    install_dependencies
    install_docker
    detect_compose
    create_directories
    download_project
    get_user_inputs
    create_docker_compose
    start_containers
    set_permissions
    display_info
}

# ═══════════════════════════════════════════════════════════════════════════════
# RUN MAIN
# ═══════════════════════════════════════════════════════════════════════════════
main "$@"
