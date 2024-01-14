# Installation with Helm

You can pull down the helm chart to use locally.  Presently, it is not in a Chart Repository.

You will note that I used a local build of the Dockerfile that I pushed to Docker up `idjohnson/opendocman:0.0.1`.  You need not trust my image.  If you build yourself, just set the values.yaml for image repository and tag.

E.g.
```
image:
  repository: myusername/opendocman
  pullPolicy: IfNotPresent
  tag: "mytag"
```

Or using helm set arguments
```
helm install opendocman -n opendocman --create-namespace --set image.repository=myusername/opendocman --set image.tag=latest --set image.pullPolicy=Always
```

## Setup

You should run the following commands to update the MariaDB dependency before installing
```
$ helm dependency update my-chart
$ helm install my-release my-chart
```

## Installation

I have found that the service IP for the MariaDB instance works best in the Configuration setup page

So I will Install
```
$ helm install opendocman -n opendocman --create-namespace --set appconfig.storageClassName=local-path --set appdata.storageClassName=managed-nfs-storage --set appdata.size=10Gi ./opendocman
NAME: opendocman
LAST DEPLOYED: Sun Jan 14 06:54:12 2024
NAMESPACE: opendocman
STATUS: deployed
REVISION: 1
NOTES:
1. Get the application URL by running these commands:
  export POD_NAME=$(kubectl get pods --namespace opendocman -l "app.kubernetes.io/name=opendocman,app.kubernetes.io/instance=opendocman" -o jsonpath="{.items[0].metadata.name}")
  export CONTAINER_PORT=$(kubectl get pod --namespace opendocman $POD_NAME -o jsonpath="{.spec.containers[0].ports[0].containerPort}")
  echo "Visit http://127.0.0.1:8080 to use your application"
  kubectl --namespace opendocman port-forward $POD_NAME 8080:$CONTAINER_PORT
```

Then look up the 'mariadb' service Cluster IP
```
$ kubectl get svc -n opendocman
NAME                 TYPE        CLUSTER-IP      EXTERNAL-IP   PORT(S)    AGE
opendocman-mariadb   ClusterIP   10.43.11.247    <none>        3306/TCP   6m59s
opendocman           ClusterIP   10.43.238.190   <none>        80/TCP     6m59s
```

And thus in my case above, for "Database Host" I would enter "10.43.11.247".

## Docs and Storage Classes

I did find issues with my own NFS storage.  I created an initContainer that would create a "docs" subfolder with proper permissions (user/group 1000).

So for the above install, I entered "/var/www/document_repository/docs" as my "Data Directory".  I had no such issues when using local-path storage class so I believe the issue lies in my local k3s configuration.



