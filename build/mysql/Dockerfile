# The database image of the compose stack of csv-import-api.
#
# The service builds from this file instead of pointing at a registry image
# (R91). The base image is pinned by digest.

FROM mysql:8.4@sha256:76f0358884cb92d9a086d24e5671c38048d3fef40f2588703d73eb55392d6b69

# The data directory is a bind mount from var/mysql, so the permissions of the
# mount point are set on the host side.
WORKDIR /var/lib/mysql
