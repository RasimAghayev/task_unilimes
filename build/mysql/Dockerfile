# The database image of the compose stack of csv-import-api.
#
# The service builds from this file instead of pointing at a registry image
# (R91). The base image is pinned by digest.

FROM mysql:26.10@sha256:f51c9c01c16b0eb5d81bb70ab7e7fcb3b96abc6a54114be3a94e8e569cae7815

# The data directory is a bind mount from var/mysql, so the permissions of the
# mount point are set on the host side.
WORKDIR /var/lib/mysql
