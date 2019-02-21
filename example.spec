# Example RPM specification file
#
# This file acts as a starting point for new projects. All applications
# that are built and deployed as part of the CMS infrastracture use RPM
# as the package format. RPMs are deployed to the various nodes in the
# platform to handle the various components of the system.

# This directive forces all references to _libdir to not care about
# the standard lib64 location for 64-bit packages. Since most development
# projects are PHP and are effectively noarch.

%ifarch x86_64
%define _libdir /usr/local/lib
%endif

# Enables use of the internal automatic dependency system
# that is a part of Townnews Package. Helps cover adding
# some dependencies that would otherwise be flagged by
# the systems' team during their code audits

%global _use_internal_dependency_generator 0
%global __find_requires %{townnews_package_find_requires}

Summary: Example Application
Name: example
Version: %{townnews_package_version}
Release: 1%{?dist}
License: Commercial
Vendor: TownNews.com
Packager: John Doe <jdoe@example.com>
Source: %{name}-%{version}.tar.gz
Group: Applications/WWW
BuildArch: noarch
BuildRoot: %{_tmppath}/%{name}-%{version}-build
BuildRequires: townnews-package
%description
A description of what this project provides. Usually a base
package includes libraries needed on all systems that use
this module.

%files
%defattr(0644,root,root,0755)
%{_libdir}/php/%{name}

### Example Manager Package

%package mgr
Summary: Example Application for CMS Manager Nodes
Group: Applications/WWW
Requires: %{name} = %{version}-%{release}
%description mgr
A description of what this package does on CMS manager nodes

%files mgr
%defattr(0644,root,root,0755)
%attr(0755,-,-) %{_bindir}/*
/etc/cron.d/*.crt
/usr/local/etc/outage-control.d/%{name}

### Example Search Package

%package search
Summary: Example Package for Search Nodes
Group: Applications/WWW
Requires: %{name} = %{version}-%{release}
%description search
Example search package

%files search
%defattr(0644,root,root,0755)
/etc/apache-solr/tncms/%{name}

### Example Admin Package

%package admin
Summary: Example Package for CMS Admin nodes
Group: Applications/WWW
Requires: %{name} = %{version}-%{release}
Requires: tncms-admin
%description admin
A description of what this package offers on CMS admin nodes

%files admin
%defattr(0644,root,root,0755)
%{_datadir}/tncms/admin/apps/%{name}

### Example Static Content Package

%package static
Summary: Example Package for Image Processing Nodes
Group: Applications/WWW
Requires: %{name} = %{version}-%{release}
%description static
A description of what this package offers on image processing nodes

%files static
%defattr(0644,root,root,0755)
%{_datadir}/content/art/%{name}

### Example Log Package

%package log
Summary: Example Package for Log Servers
Group: Applications/WWW
Requires: logsys
%description log
This package installs configuration files into Logsys for logging
activities for this application.

%files log
%defattr(0644,root,root,0755)
/usr/local/etc/logsys.d/*.ini

%prep
%setup -q
%build

%install
%{__mkdir_p} %{buildroot}%{_bindir}
%{__cp} -r bin/* %{buildroot}%{_bindir}

%{__mkdir_p} %{buildroot}%{_libdir}/php
%{__cp} -r lib/* %{buildroot}%{_libdir}/php

%{__mkdir_p} %{buildroot}%{_datadir}/tncms
%{__cp} -r share/* %{buildroot}%{_datadir}/tncms

%changelog
%townnews_package_changelog EXAMPLE
